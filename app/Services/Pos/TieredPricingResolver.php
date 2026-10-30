<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Enums\DiscountTierType;
use App\Models\Service;
use App\Models\ServicePriceTier;

final readonly class TieredPricingResolver
{
    /**
     * Menghitung harga satuan efektif dan potongan grosir otomatis untuk layanan jasa
     * (misal Fotocopy >= 100 lembar). Semua nilai dalam string dengan skala 4 desimal.
     *
     * @return array{
     *     applied_tier_id: int|null,
     *     base_unit_price: string,
     *     tier_discount_per_unit: string,
     *     final_unit_price: string,
     *     line_subtotal_gross: string,
     *     line_tier_discount_total: string,
     *     line_net_before_manual: string
     * }
     */
    public function resolve(Service $service, string $qty): array
    {
        $basePrice = number_format((float) $service->base_price, 4, '.', '');
        $normalizedQty = number_format((float) $qty, 4, '.', '');

        /** @var ServicePriceTier|null $matchedTier */
        $matchedTier = $service->priceTiers()
            ->where('is_active', true)
            ->where('min_qty', '<=', $normalizedQty)
            ->where(static function ($query) use ($normalizedQty): void {
                $query->whereNull('max_qty')
                    ->orWhere('max_qty', '>=', $normalizedQty);
            })
            ->orderByDesc('min_qty')
            ->orderBy('priority')
            ->first();

        $discountPerUnit = '0.0000';

        if ($matchedTier !== null) {
            $tierVal = number_format((float) $matchedTier->discount_value, 4, '.', '');
            $discountType = $matchedTier->discount_type;

            $discountPerUnit = match ($discountType) {
                DiscountTierType::PERCENTAGE => bcdiv(bcmul($basePrice, $tierVal, 6), '100.0000', 4),
                DiscountTierType::FIXED_DISCOUNT_PER_UNIT => $tierVal,
                DiscountTierType::SPECIAL_UNIT_PRICE => bccomp($basePrice, $tierVal, 4) === 1
                    ? bcsub($basePrice, $tierVal, 4)
                    : '0.0000',
            };
        }

        $finalUnitPrice = bcsub($basePrice, $discountPerUnit, 4);
        if (bccomp($finalUnitPrice, '0.0000', 4) === -1) {
            $finalUnitPrice = '0.0000';
            $discountPerUnit = $basePrice;
        }

        $grossSubtotal = bcmul($normalizedQty, $basePrice, 4);
        $tierDiscountTotal = bcmul($normalizedQty, $discountPerUnit, 4);
        $netTotal = bcsub($grossSubtotal, $tierDiscountTotal, 4);

        return [
            'applied_tier_id' => $matchedTier?->id,
            'base_unit_price' => $basePrice,
            'tier_discount_per_unit' => $discountPerUnit,
            'final_unit_price' => $finalUnitPrice,
            'line_subtotal_gross' => $grossSubtotal,
            'line_tier_discount_total' => $tierDiscountTotal,
            'line_net_before_manual' => $netTotal,
        ];
    }
}
