<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Enums\PromotionDiscountType;
use App\Enums\PromotionScope;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final readonly class PromotionResolver
{
    /**
     * Cari promo aktif terbaik atau verifikasi promo yang dipilih kasir.
     *
     * @param array<int, array<string, mixed>> $cartItems
     * @return array{
     *     applied_promotion: Promotion|null,
     *     promo_discount_total: string,
     *     retail_discount: string,
     *     service_discount: string,
     *     eligible_promotions: Collection<int, Promotion>
     * }
     */
    public function resolve(
        array $cartItems,
        ?int $requestedPromotionId = null,
        ?string $couponCode = null,
        ?Carbon $now = null
    ): array {
        $now = $now ?? Carbon::now();

        $activePromos = Promotion::query()
            ->with(['category', 'product', 'service'])
            ->activeNow($now)
            ->get();

        if ($activePromos->isEmpty() || empty($cartItems)) {
            return [
                'applied_promotion' => null,
                'promo_discount_total' => '0.0000',
                'retail_discount' => '0.0000',
                'service_discount' => '0.0000',
                'eligible_promotions' => collect(),
            ];
        }

        // Cache product categories
        $productCategoryMap = [];
        $retailProductIds = collect($cartItems)->where('type', 'RETAIL')->pluck('id')->filter()->unique()->all();
        if (! empty($retailProductIds)) {
            $productCategoryMap = Product::whereIn('id', $retailProductIds)->pluck('category_id', 'id')->all();
        }

        $serviceCategoryMap = [];
        $serviceIds = collect($cartItems)->where('type', 'SERVICE')->pluck('id')->filter()->unique()->all();
        if (! empty($serviceIds)) {
            $serviceCategoryMap = Service::whereIn('id', $serviceIds)->pluck('category_id', 'id')->all();
        }

        $evaluated = [];

        foreach ($activePromos as $promo) {
            // Jika promo mensyaratkan kode kupon/voucher
            if (! empty($promo->code) && $requestedPromotionId !== $promo->id) {
                if ($couponCode === null || $couponCode === '' || strcasecmp((string) $promo->code, trim($couponCode)) !== 0) {
                    continue;
                }
            }

            $calc = $this->calculateForPromo($promo, $cartItems, $productCategoryMap, $serviceCategoryMap);
            if (bccomp($calc['total'], '0.0000', 4) === 1) {
                $evaluated[] = [
                    'promo' => $promo,
                    'total' => $calc['total'],
                    'retail' => $calc['retail'],
                    'service' => $calc['service'],
                ];
            }
        }

        $eligiblePromos = collect($evaluated)->pluck('promo');

        if (empty($evaluated)) {
            return [
                'applied_promotion' => null,
                'promo_discount_total' => '0.0000',
                'retail_discount' => '0.0000',
                'service_discount' => '0.0000',
                'eligible_promotions' => $eligiblePromos,
            ];
        }

        // Jika ada requestedPromotionId tertentu dari kasir
        if ($requestedPromotionId !== null) {
            $chosen = collect($evaluated)->first(
                static fn (array $item): bool => $item['promo']->id === $requestedPromotionId
            );

            if ($chosen !== null) {
                return [
                    'applied_promotion' => $chosen['promo'],
                    'promo_discount_total' => $chosen['total'],
                    'retail_discount' => $chosen['retail'],
                    'service_discount' => $chosen['service'],
                    'eligible_promotions' => $eligiblePromos,
                ];
            }

            // Jika promo spesifik yang diminta TIDAK memenuhi syarat atau kedaluwarsa, jangan terapkan promo lain
            return [
                'applied_promotion' => null,
                'promo_discount_total' => '0.0000',
                'retail_discount' => '0.0000',
                'service_discount' => '0.0000',
                'eligible_promotions' => $eligiblePromos,
            ];
        }

        // Jika tidak ada promo spesifik yang diminta, pilih promo otomatis yang memberikan diskon terbesar
        usort($evaluated, static fn ($a, $b) => bccomp($b['total'], $a['total'], 4));
        $best = $evaluated[0];

        return [
            'applied_promotion' => $best['promo'],
            'promo_discount_total' => $best['total'],
            'retail_discount' => $best['retail'],
            'service_discount' => $best['service'],
            'eligible_promotions' => $eligiblePromos,
        ];
    }

    /**
     * Hitung nilai diskon promo terhadap keranjang.
     *
     * @param array<int, array<string, mixed>> $cartItems
     * @param array<int, int|null> $productCategoryMap
     * @param array<int, int|null> $serviceCategoryMap
     * @return array{total: string, retail: string, service: string}
     */
    private function calculateForPromo(
        Promotion $promo,
        array $cartItems,
        array $productCategoryMap,
        array $serviceCategoryMap
    ): array {
        $retailQualifying = '0.0000';
        $serviceQualifying = '0.0000';
        $grossTotal = '0.0000';

        foreach ($cartItems as $item) {
            $isRetail = ($item['type'] ?? '') === 'RETAIL';
            $itemId = (int) ($item['id'] ?? 0);
            $subtotalNet = number_format((float) ($item['subtotal_net'] ?? 0), 4, '.', '');
            $grossTotal = bcadd($grossTotal, $subtotalNet, 4);

            $qualifies = match ($promo->scope) {
                PromotionScope::ALL_ITEMS, PromotionScope::MIN_SPEND => true,
                PromotionScope::PRODUCT => $isRetail && $itemId === (int) $promo->product_id,
                PromotionScope::SERVICE => ! $isRetail && $itemId === (int) $promo->service_id,
                PromotionScope::CATEGORY => $isRetail
                    ? (($productCategoryMap[$itemId] ?? null) === (int) $promo->category_id)
                    : (($serviceCategoryMap[$itemId] ?? null) === (int) $promo->category_id),
            };

            if ($qualifies) {
                if ($isRetail) {
                    $retailQualifying = bcadd($retailQualifying, $subtotalNet, 4);
                } else {
                    $serviceQualifying = bcadd($serviceQualifying, $subtotalNet, 4);
                }
            }
        }

        // Cek syarat minimal total belanja
        $minSpend = number_format((float) $promo->min_order_amount, 4, '.', '');
        if (bccomp($minSpend, '0.0000', 4) === 1 && bccomp($grossTotal, $minSpend, 4) === -1) {
            return ['total' => '0.0000', 'retail' => '0.0000', 'service' => '0.0000'];
        }

        $totalQualifying = bcadd($retailQualifying, $serviceQualifying, 4);
        if (bccomp($totalQualifying, '0.0000', 4) !== 1) {
            return ['total' => '0.0000', 'retail' => '0.0000', 'service' => '0.0000'];
        }

        $discountVal = number_format((float) $promo->discount_value, 4, '.', '');
        $calculatedDiscount = '0.0000';

        if ($promo->discount_type === PromotionDiscountType::PERCENTAGE) {
            $raw = bcdiv(bcmul($totalQualifying, $discountVal, 6), '100.0000', 4);
            $maxDiscount = $promo->max_discount_amount !== null
                ? number_format((float) $promo->max_discount_amount, 4, '.', '')
                : null;

            if ($maxDiscount !== null && bccomp($raw, $maxDiscount, 4) === 1) {
                $calculatedDiscount = $maxDiscount;
            } else {
                $calculatedDiscount = $raw;
            }
        } else {
            // FIXED_AMOUNT
            $calculatedDiscount = bccomp($discountVal, $totalQualifying, 4) === 1
                ? $totalQualifying
                : $discountVal;
        }

        // Pastikan tidak melebihi total qualifying
        if (bccomp($calculatedDiscount, $totalQualifying, 4) === 1) {
            $calculatedDiscount = $totalQualifying;
        }

        // Alokasikan diskon ke ritel dan jasa secara proporsional
        $retailShare = '0.0000';
        $serviceShare = '0.0000';

        if (bccomp($retailQualifying, '0.0000', 4) === 1 && bccomp($serviceQualifying, '0.0000', 4) === 0) {
            $retailShare = $calculatedDiscount;
        } elseif (bccomp($serviceQualifying, '0.0000', 4) === 1 && bccomp($retailQualifying, '0.0000', 4) === 0) {
            $serviceShare = $calculatedDiscount;
        } else {
            $retailShare = bcdiv(bcmul($calculatedDiscount, $retailQualifying, 6), $totalQualifying, 4);
            $serviceShare = bcsub($calculatedDiscount, $retailShare, 4);
        }

        return [
            'total' => $calculatedDiscount,
            'retail' => $retailShare,
            'service' => $serviceShare,
        ];
    }
}
