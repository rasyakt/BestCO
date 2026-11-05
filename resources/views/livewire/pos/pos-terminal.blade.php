<div class="flex h-full min-h-0 flex-col gap-4"
    x-data="{
        initShortcuts() {
            window.addEventListener('keydown', (e) => {
                if (e.key === 'F2') {
                    e.preventDefault();
                    document.getElementById('pos-search-input')?.focus();
                } else if (e.key === 'F4' || e.key === 'F9') {
                    e.preventDefault();
                    $wire.openPaymentModal();
                } else if (e.key === 'F6') {
                    e.preventDefault();
                    $wire.set('showSpoilageModal', true);
                } else if (e.key === 'F7') {
                    e.preventDefault();
                    $wire.set('showMutationModal', true);
                } else if (e.key === 'F10') {
                    e.preventDefault();
                    if (typeof togglePosFocus === 'function') {
                        togglePosFocus();
                    }
                } else if (e.key === 'Escape') {
                    $wire.set('showPaymentModal', false);
                    $wire.set('showSpoilageModal', false);
                    $wire.set('showMutationModal', false);
                    $wire.set('completedTransactionId', null);
                }
            });
        }
    }"
    x-init="
        initShortcuts();
        $watch('$wire.feedbackMessage', val => {
            if (val && window.bestcoToast) {
                window.bestcoToast.show(val, $wire.feedbackType || 'info');
                $wire.set('feedbackMessage', null);
            }
        });
        @if($feedbackMessage)
            if (window.bestcoToast) {
                window.bestcoToast.show('{{ addslashes($feedbackMessage) }}', '{{ $feedbackType === 'success' ? 'success' : 'danger' }}');
                $wire.set('feedbackMessage', null);
            }
        @endif
    ">

    <div class="grid min-h-0 flex-1 grid-cols-1 items-stretch gap-4 lg:grid-cols-12">
        <!-- Katalog -->
        <div class="flex min-h-0 flex-col gap-3 lg:col-span-8">
            <!-- Toolbar Pencarian & Filter Ringkas -->
            <div class="card shrink-0 p-3">
                <div class="flex flex-col gap-2.5 sm:flex-row sm:items-center">
                    <div class="relative flex-1">
                        <div class="search-field w-full">
                            <x-icon name="search" />
                            <input type="text" id="pos-search-input" wire:model.live.debounce.250ms="searchQuery"
                                placeholder="Cari barang, barcode, atau layanan..."
                                class="input pr-10">
                        </div>
                        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 font-mono text-[10px] text-slate-400">F2</span>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 shrink-0">
                        <div class="segmented">
                            <button wire:click="$set('selectedCategory', 'ALL')"
                                class="{{ $selectedCategory === 'ALL' ? 'is-active' : '' }}">Semua</button>
                            <button wire:click="$set('selectedCategory', 'RETAIL')"
                                class="{{ $selectedCategory === 'RETAIL' ? 'is-active' : '' }}">Barang</button>
                            <button wire:click="$set('selectedCategory', 'SERVICE')"
                                class="{{ $selectedCategory === 'SERVICE' ? 'is-active' : '' }}">Layanan</button>
                        </div>

                        <div class="flex items-center gap-1.5">
                            <button wire:click="$set('showSpoilageModal', true)"
                                class="btn btn-sm border border-slate-200 bg-white text-slate-600 hover:border-amber-200 hover:bg-amber-50 hover:text-amber-700"
                                title="Catat barang rusak saat produksi (F6)">
                                <x-icon name="spoilage" class="h-3.5 w-3.5" />
                                <span>Rusak</span>
                                <span class="kbd text-[9px]">F6</span>
                            </button>
                            <button wire:click="$set('showMutationModal', true)"
                                class="btn btn-sm border border-slate-200 bg-white text-slate-600 hover:bg-slate-50"
                                title="Ambil bahan baku dari etalase (F7)">
                                <x-icon name="swap" class="h-3.5 w-3.5" />
                                <span>Bahan</span>
                                <span class="kbd text-[9px]">F7</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Produk (daftar ini yang bergulir sendiri) -->
            <div class="min-h-0 flex-1 overflow-y-auto pr-1 lg:pb-1">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4"
                    :class="posFocus ? '2xl:grid-cols-5' : ''">
                @foreach($retailProducts as $p)
                    @php $isLow = (float) $p->current_stock <= 5; @endphp
                    <button type="button" wire:click="addToCart('RETAIL', {{ $p->id }})"
                        class="group flex min-h-[148px] flex-col justify-between rounded-lg border border-slate-200 bg-white p-3.5 text-left transition-colors hover:border-brand-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/30">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-[10px] font-medium text-slate-400">Barang</span>
                                <span class="text-[10px] font-semibold {{ $isLow ? 'text-rose-600' : 'text-slate-500' }} num-tabular">
                                    {{ (int) $p->current_stock }} {{ $p->unit?->code }}
                                </span>
                            </div>
                            <h4 class="mt-2 line-clamp-2 text-[12px] font-semibold leading-snug text-slate-800">{{ $p->name }}</h4>
                            <p class="mt-1 font-mono text-[9px] text-slate-400">{{ $p->sku }}</p>
                        </div>
                        <div class="mt-3 flex items-end justify-between border-t border-slate-100 pt-2.5">
                            <p class="text-[13px] font-semibold text-slate-900 num-tabular">Rp {{ number_format((float) $p->selling_price, 0, ',', '.') }}</p>
                            <span class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-500 transition-colors group-hover:bg-brand-700 group-hover:text-white">
                                <x-icon name="plus" class="h-4 w-4" />
                            </span>
                        </div>
                    </button>
                @endforeach

                @foreach($services as $s)
                    <button type="button" wire:click="addToCart('SERVICE', {{ $s->id }})"
                        class="group flex min-h-[148px] flex-col justify-between rounded-lg border border-slate-200 bg-white p-3.5 text-left transition-colors hover:border-brand-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/30">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="text-[10px] font-medium text-slate-400">Layanan</span>
                                @if($s->priceTiers->isNotEmpty())
                                    <span class="text-[10px] font-medium text-amber-600">Grosir &ge; 100</span>
                                @endif
                            </div>
                            <h4 class="mt-2 line-clamp-2 text-[12px] font-semibold leading-snug text-slate-800">{{ $s->name }}</h4>
                            <p class="mt-1 font-mono text-[9px] text-slate-400">{{ $s->code }}</p>
                        </div>
                        <div class="mt-3 flex items-end justify-between border-t border-slate-100 pt-2.5">
                            <div>
                                <p class="text-[9px] text-slate-400">Per {{ $s->base_unit_name ?? 'lembar' }}</p>
                                <p class="mt-0.5 text-[13px] font-semibold text-brand-700 num-tabular">Rp {{ number_format((float) $s->base_price, 0, ',', '.') }}</p>
                            </div>
                            <span class="flex h-7 w-7 items-center justify-center rounded-md bg-slate-100 text-slate-500 transition-colors group-hover:bg-brand-700 group-hover:text-white">
                                <x-icon name="plus" class="h-4 w-4" />
                            </span>
                        </div>
                    </button>
                @endforeach
                </div>

                @if($retailProducts->isEmpty() && $services->isEmpty())
                    <div class="card">
                        <div class="empty-state">
                            <span class="empty-state-icon"><x-icon name="search" class="h-5 w-5" /></span>
                            <p class="text-sm font-semibold text-slate-700">Barang tidak ditemukan</p>
                            <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-400">Coba kata kunci lain atau ganti kategori.</p>
                            <button wire:click="$set('searchQuery', '')" class="btn btn-secondary btn-sm mt-4">Hapus pencarian</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Keranjang -->
        <div class="flex min-h-0 flex-col lg:col-span-4">
            <div class="card flex min-h-0 flex-col overflow-hidden lg:h-full">
                <div class="shrink-0 border-b border-slate-100 p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">Keranjang</p>
                            <p class="mt-0.5 text-[11px] text-slate-400">{{ count($cart) }} item</p>
                        </div>
                        @if(count($cart) > 0)
                            <button wire:click="clearCart" class="flex items-center gap-1.5 rounded px-2 py-1 text-[10px] font-medium text-rose-600 transition hover:bg-rose-50">
                                <x-icon name="trash" class="h-3.5 w-3.5" />
                                Kosongkan
                            </button>
                        @endif
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Nama pelanggan</label>
                        <input type="text" wire:model="customerName" placeholder="Siswa, guru, atau umum"
                            class="input py-2 text-xs">
                    </div>
                </div>

                <div class="min-h-0 flex-1 space-y-2 overflow-y-auto p-3">
                    @forelse($cart as $index => $item)
                        <div class="rounded-md border border-slate-200 p-3">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-[12px] font-semibold text-slate-800">{{ $item['name'] }}</span>
                                        <span class="text-[10px] font-medium text-slate-400">{{ $item['type'] === 'RETAIL' ? 'Barang' : 'Layanan' }}</span>
                                    </div>
                                    <p class="mt-1 text-[10px] text-slate-400">Rp {{ number_format($item['base_price'], 0, ',', '.') }} / unit</p>
                                </div>
                                <button wire:click="removeItem({{ $index }})" class="flex h-6 w-6 shrink-0 items-center justify-center rounded text-slate-300 transition hover:bg-rose-50 hover:text-rose-500" aria-label="Hapus item">
                                    <x-icon name="x" class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            @if($item['tier_discount_total'] > 0)
                                <p class="mt-2 rounded bg-amber-50 px-2 py-1 text-[10px] font-medium text-amber-700">
                                    Potongan grosir: -Rp {{ number_format($item['tier_discount_total'], 0, ',', '.') }}
                                </p>
                            @endif

                            <div class="mt-2.5 flex items-center justify-between border-t border-slate-100 pt-2.5">
                                <div class="inline-flex items-center overflow-hidden rounded border border-slate-300">
                                    <button wire:click="updateQty({{ $index }}, {{ $item['qty'] - 1 }})"
                                        class="flex h-7 w-7 items-center justify-center bg-slate-50 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">
                                        <x-icon name="minus" class="h-3 w-3" />
                                    </button>
                                    <input type="text" inputmode="numeric" step="1" min="1"
                                        value="{{ (int) $item['qty'] }}"
                                        data-quantity="true"
                                        wire:change="updateQty({{ $index }}, $event.target.value)"
                                        class="h-7 w-10 border-x border-slate-300 text-center text-[11px] font-semibold text-slate-800 focus:outline-none num-tabular">
                                    <button wire:click="updateQty({{ $index }}, {{ $item['qty'] + 1 }})"
                                        class="flex h-7 w-7 items-center justify-center bg-slate-50 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">
                                        <x-icon name="plus" class="h-3 w-3" />
                                    </button>
                                </div>

                                <span class="text-[13px] font-semibold text-slate-900 num-tabular">
                                    Rp {{ number_format($item['subtotal_net'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state py-10">
                            <span class="empty-state-icon"><x-icon name="cart" class="h-5 w-5" /></span>
                            <p class="text-sm font-semibold text-slate-700">Keranjang kosong</p>
                            <p class="mt-1 max-w-[180px] text-[11px] leading-relaxed text-slate-400">Klik barang atau layanan untuk menambahkan.</p>
                        </div>
                    @endforelse
                </div>

                <div class="shrink-0 border-t border-slate-100 bg-slate-50 p-4">
                    @if(count($cart) > 0)
                        <div class="space-y-1.5 text-[11px] pb-3 mb-3 border-b border-slate-200/60">
                            <div class="flex items-center justify-between text-slate-500">
                                <span>Barang</span>
                                <span class="font-medium text-slate-700 num-tabular">Rp {{ number_format($this->subtotalRetail, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-slate-500">
                                <span>Layanan</span>
                                <span class="font-medium text-slate-700 num-tabular">Rp {{ number_format($this->subtotalService, 0, ',', '.') }}</span>
                            </div>
                            @if($this->totalTierDiscount > 0)
                                <div class="flex items-center justify-between font-medium text-amber-600">
                                    <span>Potongan grosir</span>
                                    <span class="num-tabular">-Rp {{ number_format($this->totalTierDiscount, 0, ',', '.') }}</span>
                                </div>
                            @endif

                            @if((float) $promoDiscountTotal > 0)
                                <div class="flex items-center justify-between rounded-lg border border-emerald-200 bg-emerald-50/80 px-2.5 py-1.5 text-emerald-800">
                                    <div class="flex min-w-0 items-center gap-1.5">
                                        <x-icon name="sparkles" class="h-3.5 w-3.5 shrink-0 text-emerald-600" />
                                        <span class="truncate text-[10.5px] font-medium" title="{{ $appliedPromotionName }}">{{ $appliedPromotionName }}</span>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1">
                                        <span class="num-tabular font-semibold text-emerald-700">-Rp {{ number_format((float) $promoDiscountTotal, 0, ',', '.') }}</span>
                                        <button type="button" wire:click="removePromotion" class="rounded p-0.5 text-emerald-600 transition hover:bg-emerald-200/60 hover:text-rose-600" title="Lepas Promo">
                                            <x-icon name="x" class="h-3 w-3" />
                                        </button>
                                    </div>
                                </div>
                            @endif

                            <!-- Input Kupon / Promo Manual -->
                            @if($this->canUseCoupon())
                                <div class="flex items-center gap-1.5 pt-1">
                                    <input type="text" placeholder="Kode kupon/promo..."
                                        wire:model="couponCode" wire:keydown.enter="applyCoupon"
                                        class="input py-1 px-2.5 text-[10.5px] uppercase placeholder:normal-case flex-1">
                                    <button type="button" wire:click="applyCoupon" class="btn btn-secondary py-1 px-2 text-[10.5px]">
                                        Pakai
                                    </button>
                                </div>
                            @endif

                            @if($this->canUseManualDiscount())
                                <div class="flex items-start justify-between gap-3 pt-1">
                                    <span class="text-slate-500">Diskon manual</span>
                                    <div class="text-right">
                                        <div class="relative w-28">
                                            <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-[10px] font-medium text-slate-400">Rp</span>
                                            <input type="text" inputmode="numeric" step="500" min="0"
                                                wire:model.live.debounce.300ms="manualDiscountTotal"
                                                data-currency="true"
                                                class="input py-1.5 pl-7 text-right text-[11px] font-medium num-tabular">
                                        </div>
                                        <p class="mt-1 text-[10px] text-slate-400">
                                            Maks. Rp {{ number_format($this->maxManualDiscountAmount(), 0, ',', '.') }}
                                        </p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <div class="flex items-end justify-between">
                        <div>
                            <p class="text-[11px] font-medium text-slate-500">Total</p>
                            <p class="mt-0.5 text-2xl font-semibold tracking-tight text-brand-700 num-tabular">
                                Rp {{ number_format($this->grandTotal, 0, ',', '.') }}
                            </p>
                        </div>
                        <span class="text-[11px] text-slate-400">{{ count($cart) }} item</span>
                    </div>

                    <button wire:click="openPaymentModal" @disabled(count($cart) === 0)
                        class="btn btn-success btn-lg mt-3 w-full">
                        <x-icon name="cash" class="h-4 w-4" />
                        Bayar
                        <span class="kbd ml-1 border-white/20 bg-white/15 text-white/80">F4</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- PEMBAYARAN -->
    <!-- ============================================================= -->
    @if($showPaymentModal)
        @php $change = (float) $amountPaid - $this->grandTotal; @endphp
    <div class="modal-backdrop">
        <div class="modal-card payment-modal max-w-2xl" @click.outside="$wire.set('showPaymentModal', false)"
            x-data x-init="$nextTick(() => { $refs.cashInput?.focus(); $refs.cashInput?.select(); })">

                <!-- Header -->
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-3.5">
                    <div class="min-w-0">
                        <h3 class="text-[15px] font-semibold leading-tight text-slate-900">Pembayaran</h3>
                        <p class="mt-0.5 truncate text-[11px] text-slate-500">
                            {{ count($cart) }} item &bull; {{ $customerName }}
                        </p>
                    </div>
                    <button type="button" wire:click="$set('showPaymentModal', false)"
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Tutup">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <!-- Body (sengaja tanpa area scroll agar kasir tidak perlu menggulir) -->
                <div class="px-5 py-4">
                    <!-- Ringkasan: total & kembalian -->
                    <div class="grid gap-2.5 {{ $paymentMethod === 'CASH' ? 'grid-cols-5' : 'grid-cols-1' }}">
                        <div class="rounded-lg bg-slate-900 px-4 py-3 text-white {{ $paymentMethod === 'CASH' ? 'col-span-3' : '' }}">
                            <p class="text-[10px] font-medium uppercase tracking-[0.08em] text-slate-400">Total tagihan</p>
                            <p class="mt-1 text-2xl font-semibold leading-none tracking-tight num-tabular">
                                Rp {{ number_format($this->grandTotal, 0, ',', '.') }}
                            </p>
                        </div>

                        @if($paymentMethod === 'CASH')
                            <div class="col-span-2 rounded-lg border px-4 py-3 {{ $change >= 0 ? 'border-emerald-200 bg-emerald-50/70' : 'border-rose-200 bg-rose-50/70' }}">
                                <p class="text-[10px] font-medium uppercase tracking-[0.08em] {{ $change >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    {{ $change >= 0 ? 'Kembalian' : 'Kurang bayar' }}
                                </p>
                                <p class="mt-1 text-2xl font-semibold leading-none tracking-tight num-tabular {{ $change >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                    Rp {{ number_format(abs($change), 0, ',', '.') }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Daftar item keranjang -->
                    <div class="mt-3 rounded-lg border border-slate-200 bg-white p-3">
                        <p class="text-[10px] font-medium uppercase tracking-[0.08em] text-slate-400 mb-2">Item Keranjang</p>
                        <div class="space-y-2 max-h-[200px] overflow-y-auto pr-1">
                            @forelse($cart as $item)
                                <div class="flex items-center justify-between gap-2 text-xs">
                                    <div class="min-w-0 flex-1 truncate">
                                        <span class="block text-slate-800 font-medium leading-tight">{{ $item['name'] }}</span>
                                        <span class="block text-[9px] text-slate-500 truncate">
                                            {{ $item['type'] === 'RETAIL' ? 'Barang' : 'Layanan' }} &bull; Rp {{ number_format($item['base_price'], 0, ',', '.') }} / unit
                                        </span>
                                    </div>
                                    <span class="text-right num-tabular font-medium text-slate-700">
                                        {{ (int) $item['qty'] }} x Rp {{ number_format($item['subtotal_net'], 0, ',', '.') }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-[10px] text-slate-400 text-center">Keranjang kosong</p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Metode pembayaran -->
                    <div class="mt-3 grid grid-cols-2 gap-2" role="group" aria-label="Metode pembayaran">
                        <button type="button" wire:click="$set('paymentMethod', 'CASH')" aria-pressed="{{ $paymentMethod === 'CASH' ? 'true' : 'false' }}"
                            class="inline-flex items-center justify-center gap-1.5 rounded-md border px-3 py-2 text-xs font-semibold transition
                            {{ $paymentMethod === 'CASH' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-slate-300 bg-white text-slate-600 hover:border-slate-400 hover:bg-slate-50' }}">
                            <x-icon name="cash" class="h-4 w-4" />
                            Tunai
                        </button>
                        <button type="button" wire:click="$set('paymentMethod', 'QRIS')" aria-pressed="{{ $paymentMethod === 'QRIS' ? 'true' : 'false' }}"
                            class="inline-flex items-center justify-center gap-1.5 rounded-md border px-3 py-2 text-xs font-semibold transition
                            {{ $paymentMethod === 'QRIS' ? 'border-brand-600 bg-brand-50 text-brand-700' : 'border-slate-300 bg-white text-slate-600 hover:border-slate-400 hover:bg-slate-50' }}">
                            <x-icon name="qr" class="h-4 w-4" />
                            QRIS
                        </button>
                    </div>

                    @if($paymentMethod === 'CASH')
                        <!-- Uang diterima + uang pas -->
                        <div class="mt-3 flex items-end gap-2">
                            <div class="min-w-0 flex-1">
                                <label for="pos-amount-paid" class="mb-1 block text-[11px] font-medium text-slate-600">Uang diterima</label>
                                <div class="relative">
                                    <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-[11px] font-medium text-slate-400">Rp</span>
                                    <input type="text" id="pos-amount-paid" inputmode="numeric" step="1000"
                                        wire:model.live.debounce.250ms="amountPaid"
                                        data-currency="true"
                                        min="0"
                                        x-ref="cashInput"
                                        @focus="$event.target.select()"
                                        wire:keydown.enter="executeCheckout"
                                        autocomplete="off"
                                        class="input py-2 pl-8 text-lg font-semibold text-slate-900 num-tabular">
                                </div>
                            </div>
                            <button type="button" wire:click="setQuickCash({{ (int) $this->grandTotal }})"
                                title="Isi tepat sebesar total tagihan"
                                class="btn btn-secondary h-[46px] shrink-0 px-3">
                                Uang pas
                            </button>
                        </div>

                        <!-- Nominal cepat -->
                        <div class="mt-2.5 grid grid-cols-4 gap-1.5 sm:grid-cols-5">
                            @foreach([100, 200, 500, 1000, 2000, 5000, 10000, 20000, 50000, 100000] as $nominal)
                                <button type="button" wire:click="setQuickCash({{ $nominal }})"
                                    class="rounded-md border border-slate-200 bg-white px-1 py-1.5 text-[10px] font-semibold text-slate-600 transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700 sm:text-[11px]">
                                    Rp {{ number_format($nominal, 0, ',', '.') }}
                                </button>
                            @endforeach
                        </div>
                    @else
                        @php
                            $qrisImagePath = $this->getQrisImagePath();
                            $qrisExists = $qrisImagePath && file_exists(storage_path('app/public/' . $qrisImagePath));
                        @endphp

                        <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-4 text-center">
                            @if($qrisExists)
                                <img src="{{ asset('storage/' . $qrisImagePath) }}" alt="Kode QRIS"
                                    class="mx-auto h-40 w-40 rounded-md border border-slate-200 bg-white object-contain p-1.5">
                                <p class="mt-2.5 text-xs font-semibold text-slate-800">Scan QRIS untuk membayar</p>
                                <p class="mt-1 text-[11px] text-slate-500">Pembayaran otomatis masuk ke kas bank</p>
                            @else
                                <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-md border border-slate-200 bg-white text-slate-500">
                                    <x-icon name="qr" class="h-5 w-5" />
                                </span>
                                <p class="mt-2 text-xs font-semibold text-slate-800">Tunjukkan QRIS ke pembeli</p>
                                <p class="mt-1 text-[11px] text-slate-500">Pembayaran masuk ke kas bank</p>
                                @auth
                                    @if(auth()->user()->hasAnyRole(['superadmin', 'admin']))
                                        <div class="mt-3 rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-[10px] leading-relaxed text-amber-700">
                                            Upload QR code QRIS di
                                            <a href="{{ route('settings.qris') }}" class="font-semibold underline underline-offset-2 hover:text-amber-900">Pengaturan QRIS</a>.
                                        </div>
                                    @endif
                                @endauth
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="flex items-center gap-2 border-t border-slate-100 bg-slate-50 px-5 py-3">
                    <button type="button" wire:click="$set('showPaymentModal', false)" class="btn btn-secondary flex-1">
                        Batal
                        <span class="kbd hidden border-slate-300 bg-white text-[9px] sm:inline-flex">Esc</span>
                    </button>
                    <button type="button" wire:click="executeCheckout" wire:loading.attr="disabled"
                        class="btn btn-success flex-[2]">
                        <x-icon name="check-circle" class="h-4 w-4" />
                        <span wire:loading.remove wire:target="executeCheckout">Selesaikan pembayaran</span>
                        <span wire:loading wire:target="executeCheckout">Memproses…</span>
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================= -->
    <!-- BARANG RUSAK -->
    <!-- ============================================================= -->
    @if($showSpoilageModal)
        <div class="modal-backdrop">
            <div class="modal-card max-w-lg" @click.outside="$wire.set('showSpoilageModal', false)">
                <div class="modal-header">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Catat barang rusak</h3>
                        <p class="mt-0.5 text-[11px] text-slate-500">Bahan yang terbuang saat produksi</p>
                    </div>
                    <button type="button" wire:click="$set('showSpoilageModal', false)" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="modal-body space-y-4">
                    <div class="alert alert-warning">
                        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span class="leading-relaxed">Stok barang/bahan akan berkurang dan otomatis dibukukan ke beban kerugian kerusakan.</span>
                    </div>

                    <div>
                        <label class="form-label">Kategori Kerusakan</label>
                        <select wire:model.live="spoilageSource" class="select font-medium">
                            <option value="SERVICE_PRODUCTION">Layanan Jasa (Gagal Fotocopy / Print / Laminating)</option>
                            <option value="RETAIL_DAMAGE">Produk Ritel (Barang Dagangan / ATK Rusak / Pecah)</option>
                            <option value="RAW_MATERIAL_DAMAGE">Bahan Baku (Kertas HVS / Toner Langsung Rusak)</option>
                        </select>
                    </div>

                    @if($spoilageSource === 'SERVICE_PRODUCTION')
                        <div>
                            <label class="form-label">Layanan yang gagal</label>
                            <select wire:model="spoilageServiceId" class="select">
                                @foreach($allServices as $srv)
                                    <option value="{{ $srv->id }}">{{ $srv->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($spoilageSource === 'RETAIL_DAMAGE')
                        <div>
                            <label class="form-label">Pilih Produk Ritel Rusak</label>
                            <select wire:model="spoilageProductId" class="select">
                                @foreach($allRetailProducts as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} (Sisa Stok: {{ rtrim(rtrim((string)$p->current_stock, '0'), '.') }} {{ $p->unit?->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div>
                            <label class="form-label">Pilih Bahan Baku Rusak</label>
                            <select wire:model="spoilageProductId" class="select">
                                @foreach($allRawMaterials as $raw)
                                    <option value="{{ $raw->id }}">{{ $raw->name }} (Sisa Stok: {{ rtrim(rtrim((string)$raw->current_stock, '0'), '.') }} {{ $raw->unit?->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="form-label">
                                {{ $spoilageSource === 'SERVICE_PRODUCTION' ? 'Jumlah lembar gagal' : 'Jumlah barang rusak' }}
                            </label>
                            <input type="text" inputmode="numeric" step="1" min="1"
                                wire:model="spoilageFailedQty"
                                data-quantity="true"
                                class="input font-mono">
                        </div>
                        <div>
                            <label class="form-label">Penyebab</label>
                            <select wire:model.live="spoilageReason" class="select">
                                @if($spoilageSource === 'SERVICE_PRODUCTION')
                                    <option value="PAPER_JAM">Kertas macet</option>
                                    <option value="STREAKED_PRINT">Hasil bergaris</option>
                                    <option value="LAMINATING_WRINKLE">Laminating kusut</option>
                                    <option value="MISPRINT_OPERATOR">Salah setting</option>
                                    <option value="TONER_SMEAR">Nodaan tinta</option>
                                    <option value="OTHER">Lainnya (Input manual)</option>
                                @else
                                    <option value="PHYSICAL_DAMAGE">Cacat fisik / Kemasan rusak / Pecah</option>
                                    <option value="OTHER">Lainnya (Input manual)</option>
                                @endif
                            </select>
                        </div>
                    </div>

                    @if($spoilageReason === 'OTHER')
                        <div class="rounded-lg border border-amber-200 bg-amber-50/70 p-4 dark:border-amber-900/50 dark:bg-amber-950/40 space-y-2">
                            <label class="form-label flex items-center justify-between text-amber-900 dark:text-amber-200 mb-0">
                                <span class="font-semibold">Maksud / Keterangan "Lainnya"</span>
                                <span class="text-xs font-bold text-rose-600">* Wajib diisi manual</span>
                            </label>
                            <input type="text"
                                wire:model="spoilageDescription"
                                required
                                placeholder="Jelaskan secara spesifik apa penyebab kerusakannya (contoh: Basah tersiram air, bocor, dll)..."
                                class="input bg-white dark:bg-slate-900 border-amber-300 focus:border-amber-500 focus:ring-amber-500 w-full">
                            <p class="text-[11px] text-amber-700 dark:text-amber-400">Wajib menyebutkan secara jelas penyebab kerusakan saat memilih opsi Lainnya.</p>
                        </div>
                    @else
                        <div>
                            <label class="form-label">Catatan Tambahan (Opsional)</label>
                            <input type="text" wire:model="spoilageDescription" placeholder="{{ $spoilageSource === 'SERVICE_PRODUCTION' ? 'Contoh: Kertas nyangkut di roller pemanas' : 'Contoh: Barang jatuh dari etalase' }}"
                                class="input">
                        </div>
                    @endif

                    <div>
                        <label class="form-label">PIN guru</label>
                        <input type="password" maxlength="6" wire:model="spoilageTeacherPin" placeholder="6 digit"
                            class="input font-mono tracking-[0.3em]">
                    </div>
                </div>

                <div class="modal-footer">
                    <button wire:click="$set('showSpoilageModal', false)" class="btn btn-secondary">Batal</button>
                    <button wire:click="executeSpoilage" class="btn btn-warning">
                        <x-icon name="check" class="h-4 w-4" />
                        Simpan
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================= -->
    <!-- AMBIL BAHAN -->
    <!-- ============================================================= -->
    @if($showMutationModal)
        <div class="modal-backdrop">
            <div class="modal-card max-w-lg" @click.outside="$wire.set('showMutationModal', false)">
                <div class="modal-header">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Ambil bahan dari etalase</h3>
                        <p class="mt-0.5 text-[11px] text-slate-500">Pindahkan stok ke bahan baku jasa</p>
                    </div>
                    <button type="button" wire:click="$set('showMutationModal', false)" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="modal-body space-y-4">
                    <div class="alert alert-info">
                        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span class="leading-relaxed">Stok dipindah dari etalase ke bahan baku. 1 rim menjadi 500 lembar.</span>
                    </div>

                    <div>
                        <label class="form-label">Aturan konversi</label>
                        <select wire:model="selectedConversionId" class="select">
                            @foreach($unitConversions as $uc)
                                <option value="{{ $uc->id }}">
                                    1 {{ $uc->sourceProduct->unit?->code }} {{ $uc->sourceProduct->name }} &rarr; {{ (int) $uc->conversion_factor }} lembar
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Jumlah rim</label>
                            <input type="text" inputmode="numeric" step="1" min="1"
                                wire:model="mutationSourceQty"
                                data-quantity="true"
                                class="input font-mono">
                        </div>
                        <div>
                            <label class="form-label">PIN guru</label>
                            <input type="password" maxlength="6" wire:model="mutationTeacherPin" placeholder="6 digit"
                                class="input font-mono tracking-[0.3em]">
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Catatan</label>
                        <input type="text" wire:model="mutationNotes" placeholder="Contoh: Kertas habis saat jam istirahat"
                            class="input">
                    </div>
                </div>

                <div class="modal-footer">
                    <button wire:click="$set('showMutationModal', false)" class="btn btn-secondary">Batal</button>
                    <button wire:click="executeInternalMutation" class="btn btn-primary">
                        <x-icon name="swap" class="h-4 w-4" />
                        Pindahkan
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================= -->
    <!-- STRUK -->
    <!-- ============================================================= -->
    @if($completedTransaction)
        <div class="modal-backdrop">
            <div class="modal-card max-w-sm" @click.outside="$wire.set('completedTransactionId', null)">
                <div class="modal-header">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Transaksi selesai</h3>
                        <p class="mt-0.5 font-mono text-[11px] text-slate-500">{{ $completedTransaction->invoice_number }}</p>
                    </div>
                    <button type="button" wire:click="$set('completedTransactionId', null)" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="modal-body">
                    @php
                        $storeInfo = \App\Http\Controllers\StoreSettingController::getStoreInfo();
                    @endphp
                    <div id="thermal-receipt" class="rounded-xl border border-dashed border-slate-300 bg-white p-4 font-mono text-[10px] leading-relaxed text-slate-900">
                        <div class="text-center">
                            <p class="text-[13px] font-bold tracking-[0.08em]">{{ $storeInfo['store_name'] }}</p>
                            @if($storeInfo['store_tagline'])
                                <p class="text-[9px] text-slate-500">{{ $storeInfo['store_tagline'] }}</p>
                            @endif
                            @if($storeInfo['store_address_line1'])
                                <p class="text-[9px] text-slate-500">{{ $storeInfo['store_address_line1'] }}</p>
                            @endif
                            @if($storeInfo['store_address_line2'])
                                <p class="text-[9px] text-slate-500">{{ $storeInfo['store_address_line2'] }}</p>
                            @endif
                            @if($storeInfo['store_phone'] || $storeInfo['store_email'])
                                <p class="text-[9px] text-slate-500">
                                    @if($storeInfo['store_phone']){{ $storeInfo['store_phone'] }}@endif
                                    @if($storeInfo['store_phone'] && $storeInfo['store_email']) • @endif
                                    @if($storeInfo['store_email']){{ $storeInfo['store_email'] }}@endif
                                </p>
                            @endif
                            <p class="mt-2 border-y border-dashed border-slate-300 py-1 text-[9px]">No: {{ $completedTransaction->invoice_number }}</p>
                        </div>

                        <div class="mt-2 space-y-0.5 text-[9px]">
                            <div class="flex justify-between"><span>Tgl</span><span>{{ $completedTransaction->transaction_date->format('d/m/y H:i') }}</span></div>
                            <div class="flex justify-between"><span>Kasir</span><span>{{ $completedTransaction->cashier->name }}</span></div>
                            <div class="flex justify-between"><span>Pelanggan</span><span>{{ $completedTransaction->customer_name }}</span></div>
                        </div>

                        <div class="mt-2 space-y-1.5 border-y border-dashed border-slate-300 py-2">
                            @foreach($completedTransaction->items as $it)
                                <div>
                                    <p class="font-bold">{{ $it->item_name_snapshot }}</p>
                                    <div class="flex justify-between text-[9px] text-slate-500">
                                        <span>{{ (int) $it->qty }} x {{ number_format((float) $it->base_unit_price, 0, ',', '.') }}</span>
                                        <span class="text-slate-800">{{ number_format((float) $it->line_subtotal_gross, 0, ',', '.') }}</span>
                                    </div>
                                    @if((float) $it->line_total_discount > 0)
                                        <div class="flex justify-between text-[8px] text-slate-500">
                                            <span>Diskon</span>
                                            <span class="text-slate-600">-{{ number_format((float) $it->line_total_discount, 0, ',', '.') }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @php
                            // Hitung subtotal dari semua item (sebelum diskon)
                            $subtotalGross = (float) $completedTransaction->subtotal_retail + (float) $completedTransaction->subtotal_service;
                            $totalDiscount = (float) $completedTransaction->tier_discount_total + (float) $completedTransaction->manual_discount_total;
                        @endphp

                        <div class="mt-2 space-y-0.5 text-[9px]">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>Rp {{ number_format($subtotalGross, 0, ',', '.') }}</span>
                            </div>
                            
                            @if($totalDiscount > 0)
                                @if((float) $completedTransaction->tier_discount_total > 0)
                                    <div class="flex justify-between text-slate-500">
                                        <span>Diskon Grosir</span>
                                        <span>-Rp {{ number_format((float) $completedTransaction->tier_discount_total, 0, ',', '.') }}</span>
                                    </div>
                                @endif

                                @if((float) $completedTransaction->promo_discount_total > 0)
                                    <div class="flex justify-between text-slate-500">
                                        <span>Promo ({{ $completedTransaction->promo_name_snapshot ?? 'Diskon' }})</span>
                                        <span>-Rp {{ number_format((float) $completedTransaction->promo_discount_total, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                
                                @if((float) $completedTransaction->manual_discount_total > 0)
                                    <div class="flex justify-between text-slate-500">
                                        <span>Diskon Kasir</span>
                                        <span>-Rp {{ number_format((float) $completedTransaction->manual_discount_total, 0, ',', '.') }}</span>
                                    </div>
                                @endif
                                
                                <div class="flex justify-between border-t border-dashed border-slate-300 pt-0.5">
                                    <span class="font-semibold">Total Diskon</span>
                                    <span class="font-semibold">-Rp {{ number_format($totalDiscount, 0, ',', '.') }}</span>
                                </div>
                            @endif
                        </div>

                        <div class="mt-1.5 space-y-0.5 border-t border-dashed border-slate-300 pt-1.5">
                            <div class="flex justify-between font-bold text-[11px]">
                                <span>TOTAL</span>
                                <span>Rp {{ number_format((float) $completedTransaction->grand_total, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-[9px]">
                                <span>Bayar ({{ $completedTransaction->payment_method->value }})</span>
                                <span>Rp {{ number_format((float) $completedTransaction->amount_paid, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between text-[9px]">
                                <span>Kembalian</span>
                                <span>Rp {{ number_format((float) $completedTransaction->change_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        @if($storeInfo['receipt_footer_line1'] || $storeInfo['receipt_footer_line2'])
                            <div class="mt-3 border-t border-dashed border-slate-300 pt-2 text-center text-[9px] text-slate-500">
                                @if($storeInfo['receipt_footer_line1'])
                                    {{ $storeInfo['receipt_footer_line1'] }}<br>
                                @endif
                                @if($storeInfo['receipt_footer_line2'])
                                    {{ $storeInfo['receipt_footer_line2'] }}
                                @endif
                            </div>
                        @endif
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" wire:click="$set('completedTransactionId', null)" class="btn btn-secondary">Tutup</button>
                    <button type="button" onclick="window.print()" class="btn btn-dark">
                        <x-icon name="printer" class="h-4 w-4" />
                        Cetak struk
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
