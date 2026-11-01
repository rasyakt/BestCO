<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Enums\DiscountTierType;
use App\Enums\ServiceGroup;
use App\Models\Category;
use App\Models\ChartOfAccount;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceBom;
use App\Models\ServicePriceTier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class CreateServiceAction
{
    /**
     * Daftarkan master layanan jasa baru (fotocopy, print, laminating, jilid, dll)
     * beserta tarif jual, pemetaan akun pendapatan, dan resep bahan baku (BoM).
     *
     * @param array{
     *     name: string,
     *     code?: ?string,
     *     category_id?: ?int,
     *     service_group: string,
     *     base_unit_name?: string,
     *     base_price: int|float|string,
     *     boms?: array<int, array{raw_material_product_id: int|string, consumption_qty: int|float|string}>,
     *     price_tiers?: array<int, array{tier_name: string, min_qty: int|float|string, discount_type?: string, discount_value: int|float|string}>
     * } $data
     */
    public function execute(array $data): Service
    {
        return DB::transaction(function () use ($data): Service {
            $serviceGroup = ServiceGroup::from($data['service_group']);
            $basePrice = number_format((float) ($data['base_price'] ?? 0), 4, '.', '');
            $baseUnit = trim((string) ($data['base_unit_name'] ?? 'Lembar')) ?: 'Lembar';

            // Kategori Layanan Jasa (category_scope = 'SERVICE' atau id yang disediakan)
            $categoryId = $data['category_id'] ?? null;
            if (!$categoryId) {
                $category = Category::where('category_scope', 'SERVICE')->first()
                    ?? Category::where('slug', 'layanan-jasa')->firstOrFail();
                $categoryId = $category->id;
            }

            // Pemetaan akun pendapatan & potongan berdasarkan grup jasa
            $revAccountCode = match ($serviceGroup) {
                ServiceGroup::FOTOCOPY => '4-2101', // Pendapatan Jasa Fotocopy
                ServiceGroup::PRINT => '4-2102',    // Pendapatan Jasa Print
                ServiceGroup::LAMINATING, ServiceGroup::BINDING => '4-2103', // Pendapatan Jasa Laminating & Jilid
                default => '4-2101',
            };

            $revenueCoa = ChartOfAccount::where('account_code', $revAccountCode)->firstOrFail();
            $discountCoa = ChartOfAccount::where('account_code', '4-2109')->firstOrFail(); // Potongan Pendapatan Jasa

            // Generate Kode Layanan jika kosong
            $code = trim((string) ($data['code'] ?? ''));
            if ($code === '') {
                $prefix = match ($serviceGroup) {
                    ServiceGroup::FOTOCOPY => 'SRV-FC',
                    ServiceGroup::PRINT => 'SRV-PRT',
                    ServiceGroup::LAMINATING => 'SRV-LAM',
                    ServiceGroup::BINDING => 'SRV-BND',
                    ServiceGroup::SCAN => 'SRV-SCN',
                    default => 'SRV-OTH',
                };
                $slug = strtoupper((string) Str::slug(substr($data['name'], 0, 10)));
                $slug = preg_replace('/[^A-Z0-9]/', '', $slug) ?: 'ITEM';
                $code = "{$prefix}-{$slug}";
                $baseCode = $code;
                $counter = 1;
                while (Service::where('code', $code)->exists()) {
                    $code = "{$baseCode}-{$counter}";
                    $counter++;
                }
            } else {
                if (Service::where('code', $code)->exists()) {
                    throw new InvalidArgumentException("Kode layanan {$code} sudah terdaftar.");
                }
            }

            // Simpan master service
            $service = Service::create([
                'code' => $code,
                'name' => trim($data['name']),
                'category_id' => $categoryId,
                'service_group' => $serviceGroup,
                'base_unit_name' => $baseUnit,
                'base_price' => $basePrice,
                'revenue_account_id' => $revenueCoa->id,
                'discount_account_id' => $discountCoa->id,
                'is_active' => true,
            ]);

            // Simpan Resep Bahan Baku (Bill of Materials) jika ada
            if (!empty($data['boms'])) {
                $usedRawMaterialIds = [];

                foreach ($data['boms'] as $bomItem) {
                    $rawMaterialId = (int) $bomItem['raw_material_product_id'];
                    $consumptionQty = number_format((float) $bomItem['consumption_qty'], 4, '.', '');

                    if ($rawMaterialId <= 0 || bccomp($consumptionQty, '0.0000', 4) !== 1) {
                        throw new InvalidArgumentException('Data BoM layanan tidak valid.');
                    }

                    if (isset($usedRawMaterialIds[$rawMaterialId])) {
                        throw new InvalidArgumentException('Bahan baku yang sama tidak boleh muncul lebih dari satu kali pada BoM.');
                    }

                    $rawProd = Product::where('id', $rawMaterialId)
                        ->where('inventory_type', 'RAW_MATERIAL')
                        ->where('is_active', true)
                        ->first();

                    if ($rawProd === null) {
                        throw new InvalidArgumentException('Bahan baku yang dipilih harus produk bahan baku yang aktif.');
                    }

                    $usedRawMaterialIds[$rawMaterialId] = true;

                    ServiceBom::create([
                        'service_id' => $service->id,
                        'raw_material_product_id' => $rawProd->id,
                        'consumption_qty' => $consumptionQty,
                        'is_required' => true,
                        'notes' => "BoM untuk layanan {$service->name}",
                    ]);
                }
            }

            // Simpan Tier Diskon Grosir jika ada
            if (!empty($data['price_tiers'])) {
                $priority = 1;
                foreach ($data['price_tiers'] as $tier) {
                    $minQty = number_format((float) ($tier['min_qty'] ?? 1), 4, '.', '');
                    $discVal = number_format((float) ($tier['discount_value'] ?? 0), 4, '.', '');
                    $tierType = isset($tier['discount_type'])
                        ? DiscountTierType::from($tier['discount_type'])
                        : DiscountTierType::SPECIAL_UNIT_PRICE;

                    if (bccomp($minQty, '0.0000', 4) > 0 && bccomp($discVal, '0.0000', 4) > 0) {
                        ServicePriceTier::create([
                            'service_id' => $service->id,
                            'tier_name' => trim($tier['tier_name'] ?? "Grosir \u{2265} " . rtrim(rtrim($minQty, '0'), '.') . " {$baseUnit}"),
                            'min_qty' => $minQty,
                            'max_qty' => !empty($tier['max_qty']) ? number_format((float) $tier['max_qty'], 4, '.', '') : null,
                            'discount_type' => $tierType,
                            'discount_value' => $discVal,
                            'priority' => $priority++,
                            'is_active' => true,
                        ]);
                    }
                }
            }

            return $service->fresh(['category', 'revenueAccount', 'discountAccount', 'boms.rawMaterialProduct', 'priceTiers']);
        });
    }
}

