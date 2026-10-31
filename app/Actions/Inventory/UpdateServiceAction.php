<?php

declare(strict_types=1);

namespace App\Actions\Inventory;

use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceBom;
use Illuminate\Support\Facades\DB;

final class UpdateServiceAction
{
    /**
     * Perbarui data master layanan (nama, tarif harga jual dasar, satuan, status aktif, resep BoM).
     *
     * @param array{
     *     name?: string,
     *     base_price?: int|float|string,
     *     base_unit_name?: string,
     *     is_active?: bool,
     *     boms?: array<int, array{raw_material_product_id: int|string, consumption_qty: int|float|string}>
     * } $data
     */
    public function execute(Service $service, array $data): Service
    {
        return DB::transaction(function () use ($service, $data): Service {
            $updates = [];

            if (isset($data['name'])) {
                $updates['name'] = trim((string) $data['name']);
            }

            if (isset($data['base_price'])) {
                $updates['base_price'] = number_format((float) $data['base_price'], 4, '.', '');
            }

            if (isset($data['base_unit_name'])) {
                $updates['base_unit_name'] = trim((string) $data['base_unit_name']);
            }

            if (isset($data['is_active'])) {
                $updates['is_active'] = (bool) $data['is_active'];
            }

            if (!empty($updates)) {
                $service->update($updates);
            }

            // Sinkronisasi BoM jika disediakan
            if (array_key_exists('boms', $data)) {
                $service->boms()->delete();
                if (is_array($data['boms'])) {
                    foreach ($data['boms'] as $bomItem) {
                        $rawMaterialId = (int) ($bomItem['raw_material_product_id'] ?? 0);
                        $consumptionQty = number_format((float) ($bomItem['consumption_qty'] ?? 0), 4, '.', '');

                        if ($rawMaterialId > 0 && bccomp($consumptionQty, '0.0000', 4) > 0) {
                            $rawProd = Product::findOrFail($rawMaterialId);

                            ServiceBom::create([
                                'service_id' => $service->id,
                                'raw_material_product_id' => $rawProd->id,
                                'consumption_qty' => $consumptionQty,
                                'is_required' => true,
                                'notes' => "BoM untuk layanan {$service->name}",
                            ]);
                        }
                    }
                }
            }

            return $service->fresh(['category', 'revenueAccount', 'discountAccount', 'boms.rawMaterialProduct', 'priceTiers']);
        });
    }
}

