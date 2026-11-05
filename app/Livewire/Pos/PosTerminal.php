<?php

declare(strict_types=1);

namespace App\Livewire\Pos;

use App\Actions\Inventory\MutateRetailToRawMaterialAction;
use App\Actions\Inventory\RecordSpoilageAction;
use App\Actions\Pos\CheckoutPosTransactionAction;
use App\DTOs\Inventory\InternalMutationData;
use App\DTOs\Inventory\SpoilageData;
use App\DTOs\Pos\CartItemData;
use App\DTOs\Pos\CheckoutPayloadData;
use App\Enums\PaymentMethod;
use App\Enums\ShiftStatus;
use App\Enums\SpoilageReason;
use App\Enums\UserRole;
use App\Models\PosShift;
use App\Models\PosTransaction;
use App\Models\Product;
use App\Models\Service;
use App\Models\ShiftAttendance;
use App\Models\UnitConversion;
use App\Rules\StrictCurrency;
use App\Services\Pos\TieredPricingResolver;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

final class PosTerminal extends Component
{
    // Search & Filter
    public string $searchQuery = '';

    public string $selectedCategory = 'ALL';

    // Customer & Cart
    public string $customerName = 'Pelanggan Umum';

    public array $cart = []; // list of items

    public string $manualDiscountTotal = '0';

    // Promotion State
    public ?int $appliedPromotionId = null;

    public string $appliedPromotionName = '';

    public string $promoDiscountTotal = '0';

    public string $couponCode = '';

    // Payment Modal State
    public bool $showPaymentModal = false;

    public string $paymentMethod = 'CASH';

    public string $amountPaid = '0';

    // Spoilage Modal State (F6)
    public bool $showSpoilageModal = false;

    public ?int $spoilageServiceId = null;

    public string $spoilageFailedQty = '1';

    public string $spoilageReason = 'PAPER_JAM';

    public string $spoilageDescription = '';

    public string $spoilageTeacherPin = '';

    // Internal Mutation Modal State (F7 - Ambil dari Etalase)
    public bool $showMutationModal = false;

    public ?int $selectedConversionId = null;

    public string $mutationSourceQty = '1';

    public string $mutationNotes = '';

    public string $mutationTeacherPin = '';

    // Receipt Modal State
    public ?int $completedTransactionId = null;

    // Toast/Alert
    public ?string $feedbackMessage = null;

    public ?string $feedbackType = null; // 'success' or 'error'

    /**
     * Sanitasi input untuk mencegah karakter berbahaya
     */
    protected function sanitizeNumericInput(string $value, bool $allowDecimal = true): string
    {
        // Hapus semua karakter selain angka dan titik
        $cleaned = preg_replace('/[^\d.]/', '', $value);

        if (! $allowDecimal) {
            $cleaned = preg_replace('/\./', '', $cleaned);
        } else {
            // Pastikan hanya 1 titik desimal
            $parts = explode('.', $cleaned);
            if (count($parts) > 2) {
                $cleaned = $parts[0].'.'.implode('', array_slice($parts, 1));
            }
        }

        return $cleaned;
    }

    public function canUseManualDiscount(): bool
    {
        return app(\App\Services\Feature\FeatureManager::class)->canAccess('pos_manual_discount', auth()->user());
    }

    public function canUseCoupon(): bool
    {
        return app(\App\Services\Feature\FeatureManager::class)->canAccess('pos_coupon', auth()->user());
    }

    /**
     * Hook untuk sanitasi real-time pada property updates
     */
    public function updatedManualDiscountTotal($value): void
    {
        if (! $this->canUseManualDiscount()) {
            $this->manualDiscountTotal = '0';
            $this->feedbackType = 'error';
            $this->feedbackMessage = 'Akses ditolak: Anda tidak memiliki izin untuk memberikan diskon manual.';
            $this->recalculateCart();

            return;
        }

        $this->manualDiscountTotal = $this->clampManualDiscount((string) $value);
    }

    /**
     * Sisa nilai keranjang yang boleh dipotong oleh diskon manual.
     *
     * Backend menghitung diskon manual dari nilai kotor setelah diskon grosir
     * dan diskon manual per item, jadi batas di UI harus memakai basis yang sama.
     */
    public function maxManualDiscountAmount(): float
    {
        $gross = '0.0000';
        $discounts = '0.0000';

        foreach ($this->cart as $item) {
            $gross = bcadd(
                $gross,
                number_format((float) ($item['subtotal_gross'] ?? 0), 4, '.', ''),
                4
            );
            $discounts = bcadd(
                $discounts,
                number_format((float) ($item['tier_discount_total'] ?? 0), 4, '.', ''),
                4
            );
            $discounts = bcadd(
                $discounts,
                number_format((float) ($item['manual_discount'] ?? 0), 4, '.', ''),
                4
            );
        }

        // Potongan promo ikut mengurangi basis diskon manual
        $discounts = bcadd($discounts, (string) $this->promoDiscountTotal, 4);

        $max = bcsub($gross, $discounts, 4);

        return (float) (bccomp($max, '0.0000', 4) === 1 ? $max : '0.0000');
    }

    /**
     * Batasi diskon manual agar tidak pernah melebihi sisa nilai keranjang.
     */
    protected function clampManualDiscount(string $value): string
    {
        $sanitized = $this->sanitizeNumericInput($value);

        if ($sanitized === '') {
            return '0';
        }

        $max = $this->maxManualDiscountAmount();

        return (float) $sanitized > $max ? (string) $max : $sanitized;
    }

    public function updatedAmountPaid($value): void
    {
        $this->amountPaid = $this->sanitizeNumericInput($value);
    }

    public function updatedSpoilageFailedQty($value): void
    {
        $this->spoilageFailedQty = $this->sanitizeNumericInput($value, allowDecimal: false);
    }

    public function updatedMutationSourceQty($value): void
    {
        $this->mutationSourceQty = $this->sanitizeNumericInput($value, allowDecimal: false);
    }

    public function mount(): void
    {
        $firstConversion = UnitConversion::first();
        if ($firstConversion) {
            $this->selectedConversionId = $firstConversion->id;
        }

        $firstService = Service::first();
        if ($firstService) {
            $this->spoilageServiceId = $firstService->id;
        }
    }

    /**
     * Otorisasi ulang pada setiap request Livewire, karena endpoint update
     * tidak melewati middleware route POS.
     */
    public function boot(): void
    {
        $user = auth()->user();

        abort_if($user === null, 403, 'Sesi Anda telah berakhir.');

        if ($user->hasAnyRole([UserRole::SUPERADMIN, UserRole::ADMIN])) {
            return;
        }

        abort_unless($user->is_active, 403, 'Akun Anda dinonaktifkan.');

        $activeShift = PosShift::where('status', ShiftStatus::OPEN->value)
            ->latest('opened_at')
            ->first();

        abort_if($activeShift === null, 403, 'Shift kasir belum dibuka.');

        if ($user->role_type === UserRole::PIKET_SISWA) {
            $isAuthorizedOperator = ShiftAttendance::where('pos_shift_id', $activeShift->id)
                ->where('active_operator_user_id', $user->id)
                ->exists();

            abort_unless($isAuthorizedOperator, 403, 'Anda bukan operator aktif pada shift ini.');
        }
    }

    public function addToCart(string $type, int $id): void
    {
        $this->feedbackMessage = null;

        if ($type === 'RETAIL') {
            $product = Product::findOrFail($id);
            if (bccomp((string) $product->current_stock, '1.0000', 4) === -1) {
                $this->feedbackMessage = 'Stok barang '.$product->name.' habis!';
                $this->feedbackType = 'error';

                return;
            }

            // Cek apakah sudah ada di cart
            foreach ($this->cart as $key => $item) {
                if ($item['type'] === 'RETAIL' && $item['id'] === $id) {
                    $newQty = bcadd((string) $item['qty'], '1.0000', 4);
                    if (bccomp((string) $product->current_stock, $newQty, 4) === -1) {
                        $this->feedbackMessage = 'Stok tidak mencukupi untuk menambah kuantitas.';
                        $this->feedbackType = 'error';

                        return;
                    }
                    $this->cart[$key]['qty'] = (float) $newQty;
                    $this->recalculateCart();

                    return;
                }
            }

            $this->cart[] = [
                'type' => 'RETAIL',
                'id' => $product->id,
                'name' => $product->name,
                'unit' => $product->unit?->code ?? 'PCS',
                'base_price' => (float) $product->selling_price,
                'final_unit_price' => (float) $product->selling_price,
                'qty' => 1.0,
                'manual_discount' => 0.0,
                'tier_discount_per_unit' => 0.0,
                'tier_discount_total' => 0.0,
                'subtotal_gross' => (float) $product->selling_price,
                'subtotal_net' => (float) $product->selling_price,
            ];
        } else {
            $service = Service::with('boms.rawMaterial')->findOrFail($id);

            // Cek apakah sudah ada di cart
            foreach ($this->cart as $key => $item) {
                if ($item['type'] === 'SERVICE' && $item['id'] === $id) {
                    $this->cart[$key]['qty'] = (float) bcadd((string) $item['qty'], '1.0000', 4);
                    $this->recalculateCart();

                    return;
                }
            }

            $this->cart[] = [
                'type' => 'SERVICE',
                'id' => $service->id,
                'name' => $service->name,
                'unit' => $service->base_unit_name ?? 'LBR',
                'base_price' => (float) $service->base_price,
                'final_unit_price' => (float) $service->base_price,
                'qty' => 1.0,
                'manual_discount' => 0.0,
                'tier_discount_per_unit' => 0.0,
                'tier_discount_total' => 0.0,
                'subtotal_gross' => (float) $service->base_price,
                'subtotal_net' => (float) $service->base_price,
            ];
        }

        $this->recalculateCart();
    }

    public function updateQty(int $index, float $newQty): void
    {
        // Sanitasi input: hanya angka positif
        $sanitized = preg_replace('/[^\d.]/', '', (string) $newQty);
        $newQty = (float) $sanitized;

        if ($newQty <= 0) {
            $this->removeItem($index);

            return;
        }

        $this->cart[$index]['qty'] = $newQty;
        $this->recalculateCart();
    }

    public function removeItem(int $index): void
    {
        unset($this->cart[$index]);
        $this->cart = array_values($this->cart);
        $this->recalculateCart();
    }

    public function clearCart(): void
    {
        $this->cart = [];
        $this->appliedPromotionId = null;
        $this->appliedPromotionName = '';
        $this->promoDiscountTotal = '0';
        $this->couponCode = '';
        $this->recalculateCart();

        // Livewire memoizes legacy computed properties per request. Clear them so
        // the cart summary resets immediately after checkout instead of showing
        // the previous transaction's totals until the next render.
        unset($this->subtotalRetail, $this->subtotalService, $this->totalTierDiscount, $this->grandTotal);
    }

    public function recalculateCart(): void
    {
        if (! $this->canUseManualDiscount()) {
            $this->manualDiscountTotal = '0';
        }

        if (! $this->canUseCoupon()) {
            $this->couponCode = '';
        }

        $resolver = app(TieredPricingResolver::class);

        foreach ($this->cart as $key => $item) {
            $qty = number_format((float) $item['qty'], 4, '.', '');
            $manualDiscount = number_format((float) ($item['manual_discount'] ?? 0), 4, '.', '');

            if ($item['type'] === 'SERVICE') {
                $service = Service::where('id', $item['id'])->where('is_active', true)->first();
                if ($service) {
                    $pricing = $resolver->resolve($service, $qty);
                    $this->cart[$key]['base_price'] = (float) $pricing['base_unit_price'];
                    $this->cart[$key]['final_unit_price'] = (float) $pricing['final_unit_price'];
                    $this->cart[$key]['tier_discount_per_unit'] = (float) $pricing['tier_discount_per_unit'];
                    $this->cart[$key]['tier_discount_total'] = (float) $pricing['line_tier_discount_total'];
                    $this->cart[$key]['subtotal_gross'] = (float) $pricing['line_subtotal_gross'];
                    $this->cart[$key]['subtotal_net'] = (float) bcsub($pricing['line_net_before_manual'], $manualDiscount, 4);
                } else {
                    unset($this->cart[$key]);
                    $this->cart = array_values($this->cart);

                    continue;
                }
            } else {
                $gross = bcmul($qty, (string) $item['base_price'], 4);
                $net = bcsub($gross, $manualDiscount, 4);
                $this->cart[$key]['subtotal_gross'] = (float) $gross;
                $this->cart[$key]['subtotal_net'] = (float) $net;
            }
        }

        // Evaluasi promo berjadwal
        $cartForPromo = [];
        foreach ($this->cart as $item) {
            $cartForPromo[] = [
                'type' => $item['type'],
                'id' => $item['id'],
                'subtotal_net' => number_format((float) ($item['subtotal_net'] ?? 0), 4, '.', ''),
            ];
        }

        $promoResolver = app(\App\Services\Pos\PromotionResolver::class);
        $promoResult = $promoResolver->resolve(
            cartItems: $cartForPromo,
            requestedPromotionId: $this->appliedPromotionId,
            couponCode: $this->couponCode !== '' ? $this->couponCode : null,
        );

        if ($promoResult['applied_promotion'] !== null) {
            $this->appliedPromotionId = $promoResult['applied_promotion']->id;
            $this->appliedPromotionName = $promoResult['applied_promotion']->name;
            $this->promoDiscountTotal = $promoResult['promo_discount_total'];
        } else {
            $this->appliedPromotionId = null;
            $this->appliedPromotionName = '';
            $this->promoDiscountTotal = '0';
        }

        // Keranjang bisa menyusut (item dihapus atau qty dikurangi), sehingga
        // diskon manual yang sudah diinput harus ikut dibatasi ulang.
        $this->manualDiscountTotal = $this->clampManualDiscount($this->manualDiscountTotal);

        unset($this->subtotalRetail, $this->subtotalService, $this->totalTierDiscount, $this->grandTotal);
    }

    public function applyCoupon(): void
    {
        if (! $this->canUseCoupon()) {
            $this->feedbackType = 'error';
            $this->feedbackMessage = 'Akses ditolak: Anda tidak memiliki izin untuk menggunakan kupon promo.';

            return;
        }

        $this->recalculateCart();
        if ($this->appliedPromotionId !== null) {
            $this->feedbackType = 'success';
            $this->feedbackMessage = "Kupon promo berhasil diterapkan: {$this->appliedPromotionName}";
        } else {
            $this->feedbackType = 'error';
            $this->feedbackMessage = 'Kode kupon tidak valid atau keranjang belum memenuhi syarat promo.';
        }
    }

    public function removePromotion(): void
    {
        $this->appliedPromotionId = null;
        $this->appliedPromotionName = '';
        $this->promoDiscountTotal = '0';
        $this->couponCode = '';
        $this->recalculateCart();
        $this->feedbackType = 'info';
        $this->feedbackMessage = 'Promo/kupon dilepas.';
    }

    public function getSubtotalRetailProperty(): float
    {
        return (float) collect($this->cart)
            ->where('type', 'RETAIL')
            ->sum('subtotal_gross');
    }

    public function getSubtotalServiceProperty(): float
    {
        return (float) collect($this->cart)
            ->where('type', 'SERVICE')
            ->sum('subtotal_gross');
    }

    public function getTotalTierDiscountProperty(): float
    {
        return (float) collect($this->cart)
            ->sum('tier_discount_total');
    }

    public function getGrandTotalProperty(): float
    {
        $gross = bcadd((string) $this->subtotalRetail, (string) $this->subtotalService, 4);
        $discounts = bcadd(
            bcadd((string) $this->totalTierDiscount, (string) $this->manualDiscountTotal, 4),
            (string) $this->promoDiscountTotal,
            4
        );
        $grand = bcsub($gross, $discounts, 4);

        return (float) (bccomp($grand, '0.0000', 4) === 1 ? $grand : '0.0000');
    }

    public function openPaymentModal(): void
    {
        if (empty($this->cart)) {
            $this->feedbackMessage = 'Keranjang belanja masih kosong.';
            $this->feedbackType = 'error';

            return;
        }

        // Pengaman terakhir sebelum pembayaran: diskon manual tidak boleh
        // melebihi sisa nilai keranjang.
        $this->manualDiscountTotal = $this->clampManualDiscount($this->manualDiscountTotal);

        $this->amountPaid = (string) $this->grandTotal;
        $this->showPaymentModal = true;
    }

    public function setQuickCash(int $amount): void
    {
        $this->amountPaid = (string) $amount;
    }

    public function getQrisImagePath(): ?string
    {
        $setting = \App\Models\SystemSetting::where('setting_key', 'pos_qris_image_path')->first();
        
        return $setting?->setting_value['path'] ?? null;
    }

    public function executeCheckout(CheckoutPosTransactionAction $action): void
    {
        // Pastikan diskon manual sudah dibatasi sebelum validasi & checkout.
        $this->manualDiscountTotal = $this->clampManualDiscount($this->manualDiscountTotal);

        // Validasi input
        $this->validate([
            'amountPaid' => ['required', new StrictCurrency(min: 0)],
            'manualDiscountTotal' => ['nullable', new StrictCurrency(
                min: 0,
                max: $this->maxManualDiscountAmount(),
                required: false
            )],
        ], [], [
            'amountPaid' => 'jumlah pembayaran',
            'manualDiscountTotal' => 'diskon manual',
        ]);

        $activeShift = PosShift::where('status', ShiftStatus::OPEN->value)
            ->latest('opened_at')
            ->first();

        if (! $activeShift) {
            $this->feedbackMessage = 'Shift kasir belum aktif.';
            $this->feedbackType = 'error';
            $this->showPaymentModal = false;

            return;
        }

        $paid = number_format((float) $this->amountPaid, 4, '.', '');
        $grand = number_format((float) $this->grandTotal, 4, '.', '');

        if ($this->paymentMethod === 'CASH' && bccomp($paid, $grand, 4) === -1) {
            $this->feedbackMessage = 'Uang pembayaran tunai kurang dari Grand Total!';
            $this->feedbackType = 'error';

            return;
        }

        if (bccomp((string) $this->manualDiscountTotal, '0', 4) === 1 && ! $this->canUseManualDiscount()) {
            $this->feedbackMessage = 'Akses ditolak: Anda tidak memiliki izin untuk memberikan diskon manual.';
            $this->feedbackType = 'error';
            $this->showPaymentModal = false;

            return;
        }

        if ($this->appliedPromotionId !== null && $this->couponCode !== '' && ! $this->canUseCoupon()) {
            $this->feedbackMessage = 'Akses ditolak: Anda tidak memiliki izin untuk menggunakan kupon promo.';
            $this->feedbackType = 'error';
            $this->showPaymentModal = false;

            return;
        }

        $itemsPayload = [];
        foreach ($this->cart as $item) {
            $itemsPayload[] = new CartItemData(
                itemType: $item['type'] === 'RETAIL' ? 'RETAIL_PRODUCT' : 'SERVICE',
                productId: $item['type'] === 'RETAIL' ? (string) $item['id'] : null,
                serviceId: $item['type'] === 'SERVICE' ? (string) $item['id'] : null,
                qty: number_format((float) $item['qty'], 4, '.', ''),
                manualDiscountAmount: number_format((float) ($item['manual_discount'] ?? 0), 4, '.', ''),
            );
        }

        try {
            $transaction = $action->execute(new CheckoutPayloadData(
                posShiftId: (string) $activeShift->id,
                cashierUserId: (string) auth()->id(),
                customerName: $this->customerName,
                paymentMethod: PaymentMethod::from($this->paymentMethod),
                amountPaid: $paid,
                manualDiscountTotal: number_format((float) $this->manualDiscountTotal, 4, '.', ''),
                items: $itemsPayload,
                appliedPromotionId: $this->appliedPromotionId,
            ));

            $this->completedTransactionId = $transaction->id;
            $this->showPaymentModal = false;
            $this->clearCart();
            $this->customerName = 'Pelanggan Umum';
            $this->feedbackMessage = 'Transaksi '.$transaction->invoice_number.' sukses diselesaikan!';
            $this->feedbackType = 'success';
        } catch (\Throwable $e) {
            $this->feedbackMessage = 'Gagal memproses transaksi: '.$e->getMessage();
            $this->feedbackType = 'error';
        }
    }

    public function executeSpoilage(RecordSpoilageAction $action): void
    {
        $activeShift = PosShift::where('status', 'OPEN')->latest('opened_at')->first();
        if (! $activeShift) {
            $this->feedbackMessage = 'Shift belum dibuka.';
            $this->feedbackType = 'error';

            return;
        }

        $teacher = $activeShift->supervisorTeacher;
        if (! Hash::check($this->spoilageTeacherPin, $teacher->pin_code)) {
            $this->feedbackMessage = 'PIN Guru Supervisor salah.';
            $this->feedbackType = 'error';

            return;
        }

        try {
            $log = $action->execute(new SpoilageData(
                posShiftId: (string) $activeShift->id,
                spoilageSource: 'SERVICE_PRODUCTION',
                serviceId: (string) $this->spoilageServiceId,
                failedOutputQty: number_format((float) $this->spoilageFailedQty, 4, '.', ''),
                reasonCategory: SpoilageReason::from($this->spoilageReason),
                reasonDescription: $this->spoilageDescription ?: 'Kertas macet / gagal cetak fotocopy',
                reportedByUserId: (string) auth()->id(),
                verifiedByUserId: (string) $teacher->id,
                directItems: [],
            ));

            $this->showSpoilageModal = false;
            $this->spoilageTeacherPin = '';
            $this->feedbackMessage = 'Barang rusak '.$log->spoilage_number.' berhasil dicatat (HPP terbebankan: Rp '.number_format((float) $log->total_spoilage_cost, 0, ',', '.').')';
            $this->feedbackType = 'success';
        } catch (\Throwable $e) {
            $this->feedbackMessage = 'Gagal mencatat barang rusak: '.$e->getMessage();
            $this->feedbackType = 'error';
        }
    }

    public function executeInternalMutation(MutateRetailToRawMaterialAction $action): void
    {
        $activeShift = PosShift::where('status', 'OPEN')->latest('opened_at')->first();
        if (! $activeShift) {
            $this->feedbackMessage = 'Shift belum dibuka.';
            $this->feedbackType = 'error';

            return;
        }

        $teacher = $activeShift->supervisorTeacher;
        if (! Hash::check($this->mutationTeacherPin, $teacher->pin_code)) {
            $this->feedbackMessage = 'PIN Guru Supervisor salah.';
            $this->feedbackType = 'error';

            return;
        }

        try {
            $mutation = $action->execute(new InternalMutationData(
                posShiftId: (string) $activeShift->id,
                unitConversionId: (string) $this->selectedConversionId,
                sourceQtyToDeduct: number_format((float) $this->mutationSourceQty, 4, '.', ''),
                requestedByUserId: (string) auth()->id(),
                approvedByUserId: (string) $teacher->id,
                notes: $this->mutationNotes ?: 'Ambil kertas dari etalase untuk bahan baku fotocopy',
            ));

            $this->showMutationModal = false;
            $this->mutationTeacherPin = '';
            $this->feedbackMessage = 'Mutasi '.$mutation->mutation_number.' sukses (+500 Lembar kertas bahan baku ditambahkan)!';
            $this->feedbackType = 'success';
        } catch (\Throwable $e) {
            $this->feedbackMessage = 'Gagal mutasi internal: '.$e->getMessage();
            $this->feedbackType = 'error';
        }
    }

    public function render()
    {
        $productsQuery = Product::where('inventory_type', 'RETAIL')
            ->where('is_active', true);

        if ($this->searchQuery !== '') {
            $productsQuery->where(function ($q) {
                $q->where('name', 'like', '%'.$this->searchQuery.'%')
                    ->orWhere('sku', 'like', '%'.$this->searchQuery.'%');
            });
        }

        $servicesQuery = Service::with('priceTiers')
            ->where('is_active', true);

        if ($this->searchQuery !== '') {
            $servicesQuery->where(function ($q) {
                $q->where('name', 'like', '%'.$this->searchQuery.'%')
                    ->orWhere('code', 'like', '%'.$this->searchQuery.'%');
            });
        }

        $retailProducts = ($this->selectedCategory === 'ALL' || $this->selectedCategory === 'RETAIL')
            ? $productsQuery->get()
            : collect();

        $services = ($this->selectedCategory === 'ALL' || $this->selectedCategory === 'SERVICE')
            ? $servicesQuery->get()
            : collect();

        $unitConversions = UnitConversion::with(['sourceProduct', 'targetProduct'])->get();
        $allServices = Service::all();
        $completedTransaction = $this->completedTransactionId
            ? PosTransaction::with(['items.product', 'items.service', 'cashier'])->find($this->completedTransactionId)
            : null;

        $activeShift = PosShift::with('supervisorTeacher')
            ->where('status', ShiftStatus::OPEN->value)
            ->latest('opened_at')
            ->first();

        return view('livewire.pos.pos-terminal', compact(
            'retailProducts',
            'services',
            'unitConversions',
            'allServices',
            'completedTransaction',
            'activeShift'
        ))->layout('layouts.app', ['title' => 'Terminal POS Kasir — BESTCO']);
    }
}
