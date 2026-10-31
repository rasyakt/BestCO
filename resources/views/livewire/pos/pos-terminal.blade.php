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
                } else if (e.key === 'Escape') {
                    $wire.set('showPaymentModal', false);
                    $wire.set('showSpoilageModal', false);
                    $wire.set('showMutationModal', false);
                    $wire.set('completedTransactionId', null);
                }
            });
        }
    }"
    x-init="initShortcuts()">

    <!-- Header -->
    <div class="flex shrink-0 flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <h1 class="page-title">Kasir</h1>
            <p class="page-subtitle">Pilih barang atau layanan, lalu selesaikan pembayaran.</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            @if($activeShift)
                <span class="inline-flex items-center gap-1.5 text-[11px] font-medium text-emerald-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                    Shift {{ $activeShift->shift_code }}
                </span>
            @else
                <a href="{{ route('shift.index') }}" class="inline-flex items-center gap-1.5 text-[11px] font-medium text-amber-700 hover:text-amber-800">
                    <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                    Shift belum dibuka
                    <x-icon name="arrow-right" class="h-3 w-3" />
                </a>
            @endif
        </div>
    </div>

    <!-- Feedback -->
    @if($feedbackMessage)
        <div class="alert shrink-0 {{ $feedbackType === 'success' ? 'alert-success' : 'alert-danger' }}">
            <x-icon :name="$feedbackType === 'success' ? 'check-circle' : 'x-circle'" class="mt-0.5 h-4 w-4 shrink-0" />
            <span class="flex-1 leading-relaxed">{{ $feedbackMessage }}</span>
            <button wire:click="$set('feedbackMessage', null)" class="rounded p-0.5 opacity-60 transition hover:bg-black/5 hover:opacity-100">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    @endif

    <div class="grid min-h-0 flex-1 grid-cols-1 items-stretch gap-5 lg:grid-cols-12">
        <!-- Katalog -->
        <div class="flex min-h-0 flex-col gap-4 lg:col-span-8">
            <div class="card shrink-0 p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="search-field flex-1">
                        <x-icon name="search" />
                        <input type="text" id="pos-search-input" wire:model.live.debounce.250ms="searchQuery"
                            placeholder="Cari barang, barcode, atau layanan..."
                            class="input">
                    </div>

                    <div class="segmented shrink-0">
                        <button wire:click="$set('selectedCategory', 'ALL')"
                            class="{{ $selectedCategory === 'ALL' ? 'is-active' : '' }}">Semua</button>
                        <button wire:click="$set('selectedCategory', 'RETAIL')"
                            class="{{ $selectedCategory === 'RETAIL' ? 'is-active' : '' }}">Barang</button>
                        <button wire:click="$set('selectedCategory', 'SERVICE')"
                            class="{{ $selectedCategory === 'SERVICE' ? 'is-active' : '' }}">Layanan</button>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
                    <p class="hidden text-[10px] text-slate-400 sm:block">
                        <span class="kbd">F2</span> Cari
                        <span class="ml-2 kbd">F4</span> Bayar
                        <span class="ml-2 kbd">F6</span> Barang rusak
                        <span class="ml-2 kbd">F7</span> Ambil bahan
                    </p>
                    <div class="flex items-center gap-2">
                        <button wire:click="$set('showSpoilageModal', true)"
                            class="btn btn-sm border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100">
                            <x-icon name="spoilage" class="h-3.5 w-3.5" />
                            Barang rusak
                        </button>
                        <button wire:click="$set('showMutationModal', true)"
                            class="btn btn-sm border border-slate-300 bg-white text-slate-600 hover:bg-slate-50">
                            <x-icon name="swap" class="h-3.5 w-3.5" />
                            Ambil bahan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Produk (daftar ini yang bergulir sendiri) -->
            <div class="min-h-0 flex-1 overflow-y-auto pr-1 lg:pb-1">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
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
                                <p class="text-[9px] text-slate-400">Per {{ $s->unit_name ?? 'lembar' }}</p>
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
                        <button wire:click="clearCart" class="flex items-center gap-1.5 rounded px-2 py-1 text-[10px] font-medium text-rose-600 transition hover:bg-rose-50">
                            <x-icon name="trash" class="h-3.5 w-3.5" />
                            Kosongkan
                        </button>
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
                                    <input type="number" step="1" min="1" value="{{ (int) $item['qty'] }}"
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
                    <div class="space-y-1.5 text-[11px]">
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

                        <div class="flex items-center justify-between gap-3 pt-1">
                            <span class="text-slate-500">Diskon manual</span>
                            <div class="relative w-28">
                                <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-[10px] font-medium text-slate-400">Rp</span>
                                <input type="number" step="500" min="0" wire:model.live.debounce.300ms="manualDiscountTotal"
                                    class="input py-1.5 pl-7 text-right text-[11px] font-medium num-tabular">
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex items-end justify-between border-t border-slate-200 pt-3">
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
        <div class="modal-backdrop">
            <div class="modal-card max-w-lg" @click.outside="$wire.set('showPaymentModal', false)">
                <div class="modal-header">
                    <div>
                        <h3 class="text-sm font-semibold text-slate-900">Pembayaran</h3>
                        <p class="mt-0.5 text-[11px] text-slate-500">{{ count($cart) }} item &bull; {{ $customerName }}</p>
                    </div>
                    <button wire:click="$set('showPaymentModal', false)" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="modal-body">
                    <div class="rounded-md bg-slate-900 px-5 py-4 text-white">
                        <p class="text-[11px] text-slate-400">Total tagihan</p>
                        <p class="mt-1 text-3xl font-semibold tracking-tight num-tabular">Rp {{ number_format($this->grandTotal, 0, ',', '.') }}</p>
                    </div>

                    <div>
                        <label class="form-label">Metode pembayaran</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="$set('paymentMethod', 'CASH')"
                                class="flex items-center justify-center gap-2 rounded-md border px-3 py-3 text-xs font-semibold transition
                                {{ $paymentMethod === 'CASH' ? 'border-brand-600 bg-brand-50 text-brand-800' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50' }}">
                                <x-icon name="cash" class="h-4 w-4" />
                                Tunai
                            </button>
                            <button type="button" wire:click="$set('paymentMethod', 'QRIS')"
                                class="flex items-center justify-center gap-2 rounded-md border px-3 py-3 text-xs font-semibold transition
                                {{ $paymentMethod === 'QRIS' ? 'border-brand-600 bg-brand-50 text-brand-800' : 'border-slate-300 bg-white text-slate-600 hover:bg-slate-50' }}">
                                <x-icon name="qr" class="h-4 w-4" />
                                QRIS
                            </button>
                        </div>
                    </div>

                    @if($paymentMethod === 'CASH')
                        <div class="space-y-2.5">
                            <div>
                                <label class="form-label">Uang diterima (Rp)</label>
                                <input type="number" step="1000" wire:model.live="amountPaid"
                                    class="input py-3 text-lg font-semibold text-slate-900 num-tabular">
                            </div>

                            <div class="grid grid-cols-4 gap-1.5">
                                <button type="button" wire:click="setQuickCash({{ (int) $this->grandTotal }})" class="rounded border border-slate-300 bg-white px-2 py-2 text-[10px] font-medium text-slate-600 transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700">Uang pas</button>
                                <button type="button" wire:click="setQuickCash(20000)" class="rounded border border-slate-300 bg-white px-2 py-2 text-[10px] font-medium text-slate-600 transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700">20rb</button>
                                <button type="button" wire:click="setQuickCash(50000)" class="rounded border border-slate-300 bg-white px-2 py-2 text-[10px] font-medium text-slate-600 transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700">50rb</button>
                                <button type="button" wire:click="setQuickCash(100000)" class="rounded border border-slate-300 bg-white px-2 py-2 text-[10px] font-medium text-slate-600 transition hover:border-brand-400 hover:bg-brand-50 hover:text-brand-700">100rb</button>
                            </div>
                        </div>

                        @php $change = (float) $amountPaid - $this->grandTotal; @endphp
                        <div class="flex items-center justify-between rounded-md border px-4 py-3
                            {{ $change >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
                            <span class="text-[11px] font-medium {{ $change >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $change >= 0 ? 'Kembalian' : 'Kurang bayar' }}
                            </span>
                            <span class="text-lg font-semibold num-tabular {{ $change >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                Rp {{ number_format(abs($change), 0, ',', '.') }}
                            </span>
                        </div>
                    @else
                        <div class="rounded-md border border-dashed border-slate-300 bg-slate-50 p-5 text-center">
                            <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-md bg-white text-slate-700 ring-1 ring-slate-200">
                                <x-icon name="qr" class="h-10 w-10" />
                            </span>
                            <p class="mt-3 text-xs font-semibold text-slate-700">Tunjukkan QRIS ke pembeli</p>
                            <p class="mt-1 text-[11px] leading-relaxed text-slate-400">Pembayaran langsung masuk ke kas bank.</p>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" wire:click="$set('showPaymentModal', false)" class="btn btn-secondary">Batal</button>
                    <button type="button" wire:click="executeCheckout" wire:loading.attr="disabled"
                        class="btn btn-success">
                        <x-icon name="check-circle" class="h-4 w-4" />
                        Selesaikan pembayaran
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
                    <button wire:click="$set('showSpoilageModal', false)" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning">
                        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span class="leading-relaxed">Stok bahan berkurang dan otomatis dicatat sebagai beban kerusakan.</span>
                    </div>

                    <div>
                        <label class="form-label">Layanan yang gagal</label>
                        <select wire:model="spoilageServiceId" class="select">
                            @foreach($allServices as $srv)
                                <option value="{{ $srv->id }}">{{ $srv->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Jumlah lembar gagal</label>
                            <input type="number" step="1" min="1" wire:model="spoilageFailedQty" class="input font-mono">
                        </div>
                        <div>
                            <label class="form-label">Penyebab</label>
                            <select wire:model="spoilageReason" class="select">
                                <option value="PAPER_JAM">Kertas macet</option>
                                <option value="PRINT_STREAK">Hasil bergaris</option>
                                <option value="LAMINATION_WRINKLE">Laminating kusut</option>
                                <option value="OPERATOR_ERROR">Salah setting</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Keterangan</label>
                        <input type="text" wire:model="spoilageDescription" placeholder="Contoh: Kertas nyangkut di mesin"
                            class="input">
                    </div>

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
                    <button wire:click="$set('showMutationModal', false)" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="modal-body">
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
                            <input type="number" step="1" min="1" wire:model="mutationSourceQty" class="input font-mono">
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
                    <button wire:click="$set('completedTransactionId', null)" class="flex h-8 w-8 items-center justify-center rounded text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="Tutup">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="modal-body">
                    <div id="thermal-receipt" class="rounded border border-dashed border-slate-300 bg-white p-4 font-mono text-[10px] leading-relaxed text-slate-900">
                        <div class="text-center">
                            <p class="text-[13px] font-bold tracking-[0.08em]">BESTCO</p>
                            <p class="text-[9px] text-slate-500">Teaching Factory MPLB</p>
                            <p class="text-[9px] text-slate-500">SMK Business Center</p>
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
                                        <span class="text-slate-800">{{ number_format((float) $it->line_net_total, 0, ',', '.') }}</span>
                                    </div>
                                    @if((float) $it->line_total_discount > 0)
                                        <p class="text-right text-[8px] text-slate-400">Diskon: -{{ number_format((float) $it->line_total_discount, 0, ',', '.') }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-2 space-y-1">
                            <div class="flex justify-between font-bold"><span>Total</span><span>Rp {{ number_format((float) $completedTransaction->grand_total, 0, ',', '.') }}</span></div>
                            <div class="flex justify-between text-[9px]"><span>Bayar ({{ $completedTransaction->payment_method->value }})</span><span>Rp {{ number_format((float) $completedTransaction->amount_paid, 0, ',', '.') }}</span></div>
                            <div class="flex justify-between text-[9px]"><span>Kembalian</span><span>Rp {{ number_format((float) $completedTransaction->change_amount, 0, ',', '.') }}</span></div>
                        </div>

                        <div class="mt-3 border-t border-dashed border-slate-300 pt-2 text-center text-[9px] text-slate-500">
                            Terima kasih<br>
                            Laboratorium Praktik MPLB
                        </div>
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
