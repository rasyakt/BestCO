<div class="space-y-5"
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

    <!-- Compact workspace header -->
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="page-eyebrow">
                <span class="h-1.5 w-1.5 rounded-full {{ $activeShift ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                {{ $activeShift ? 'Terminal Kasir Aktif' : 'Menunggu Shift Dibuka' }}
            </div>
            <h1 class="text-xl font-extrabold tracking-tight text-slate-900">POS Workspace</h1>
            <p class="mt-1 text-[12px] text-slate-500">Pilih produk atau layanan, atur kuantitas, lalu selesaikan pembayaran.</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if($activeShift)
                <span class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[10px] font-bold uppercase tracking-wide text-emerald-700">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    <span class="whitespace-nowrap">Shift {{ $activeShift->shift_code }}</span>
                    <span class="sm:hidden">Aktif</span>
                    <span class="hidden sm:inline">Terbuka</span>
                </span>
            @else
                <a href="{{ route('shift.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-[10px] font-bold uppercase tracking-wide text-amber-700 transition hover:bg-amber-100">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-60"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
                    </span>
                    Shift Belum Dibuka
                    <x-icon name="arrow-right" class="h-3 w-3" />
                </a>
            @endif
            <div class="hidden items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-[10px] font-semibold text-slate-500 md:flex">
                <span class="kbd">F4</span> Bayar
                <span class="kbd">F6</span> Rusak
                <span class="kbd">F7</span> Mutasi
            </div>
        </div>
    </div>

    <!-- Feedback toast -->
    @if($feedbackMessage)
        <div class="alert {{ $feedbackType === 'success' ? 'alert-success' : 'alert-danger' }} shadow-sm">
            <x-icon :name="$feedbackType === 'success' ? 'check-circle' : 'x-circle'" class="mt-0.5 h-5 w-5 shrink-0" />
            <span class="flex-1 leading-relaxed">{{ $feedbackMessage }}</span>
            <button wire:click="$set('feedbackMessage', null)" class="rounded-lg p-0.5 opacity-60 transition hover:bg-black/5 hover:opacity-100">
                <x-icon name="x" class="h-4 w-4" />
            </button>
        </div>
    @endif

    <div class="grid grid-cols-1 items-start gap-5 lg:grid-cols-12">
        <!-- Catalog -->
        <div class="space-y-4 lg:col-span-8">
            <!-- Search & categories -->
            <div class="card p-4">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="search-field flex-1">
                        <x-icon name="search" />
                        <input type="text" id="pos-search-input" wire:model.live.debounce.250ms="searchQuery"
                            placeholder="Cari nama barang, barcode, SKU, atau jasa..."
                            class="input py-3 pr-16">
                        <span class="pointer-events-none absolute right-3 top-1/2 hidden -translate-y-1/2 items-center gap-1 sm:flex">
                            <span class="kbd">F2</span>
                        </span>
                    </div>

                    <div class="segmented shrink-0">
                        <button wire:click="$set('selectedCategory', 'ALL')"
                            class="{{ $selectedCategory === 'ALL' ? 'is-active' : '' }}">Semua</button>
                        <button wire:click="$set('selectedCategory', 'RETAIL')"
                            class="{{ $selectedCategory === 'RETAIL' ? 'is-active' : '' }}">ATK Etalase</button>
                        <button wire:click="$set('selectedCategory', 'SERVICE')"
                            class="{{ $selectedCategory === 'SERVICE' ? 'is-active' : '' }}">Layanan Jasa</button>
                    </div>
                </div>

                <div class="mt-3 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-3">
                    <div class="hidden items-center gap-2 text-[10px] font-medium text-slate-400 sm:flex">
                        <span class="inline-flex items-center gap-1"><span class="kbd">F2</span> Cari</span>
                        <span class="inline-flex items-center gap-1"><span class="kbd">F4</span> Bayar</span>
                        <span class="inline-flex items-center gap-1"><span class="kbd">F6</span> Rusak</span>
                        <span class="inline-flex items-center gap-1"><span class="kbd">F7</span> Ambil Etalase</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="$set('showSpoilageModal', true)"
                            class="btn btn-sm border border-amber-200 bg-amber-50 text-amber-700 hover:bg-amber-100">
                            <x-icon name="spoilage" class="h-3.5 w-3.5" />
                            Catat Rusak
                        </button>
                        <button wire:click="$set('showMutationModal', true)"
                            class="btn btn-sm border border-indigo-200 bg-indigo-50 text-indigo-700 hover:bg-indigo-100">
                            <x-icon name="swap" class="h-3.5 w-3.5" />
                            Ambil dari Etalase
                        </button>
                    </div>
                </div>
            </div>

            <!-- Product grid -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
                @foreach($retailProducts as $p)
                    @php $isLow = (float) $p->current_stock <= 5; @endphp
                    <button type="button" wire:click="addToCart('RETAIL', {{ $p->id }})"
                        class="group flex min-h-[158px] flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-3.5 text-left shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/15">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-sky-50 text-sky-600">
                                    <x-icon name="package" class="h-4 w-4" />
                                </span>
                                <span class="badge {{ $isLow ? 'badge-danger' : 'badge-success' }}">
                                    {{ (int) $p->current_stock }} {{ $p->unit?->code }}
                                </span>
                            </div>
                            <h4 class="mt-2.5 line-clamp-2 text-[12px] font-bold leading-snug text-slate-800">{{ $p->name }}</h4>
                            <p class="mt-1 font-mono text-[9px] text-slate-400">{{ $p->sku }}</p>
                        </div>
                        <div class="mt-3 flex items-end justify-between border-t border-slate-100 pt-2.5">
                            <div>
                                <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-slate-400">Harga</p>
                                <p class="mt-0.5 text-[13px] font-extrabold text-slate-900 num-tabular">Rp {{ number_format((float) $p->selling_price, 0, ',', '.') }}</p>
                            </div>
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-50 text-brand-700 transition group-hover:bg-brand-700 group-hover:text-white">
                                <x-icon name="plus" class="h-4 w-4" />
                            </span>
                        </div>
                    </button>
                @endforeach

                @foreach($services as $s)
                    <button type="button" wire:click="addToCart('SERVICE', {{ $s->id }})"
                        class="group flex min-h-[158px] flex-col justify-between rounded-2xl border border-slate-200/80 bg-white p-3.5 text-left shadow-sm transition-all duration-150 hover:-translate-y-0.5 hover:border-brand-300 hover:shadow-lg focus-visible:outline-none focus-visible:ring-4 focus-visible:ring-brand-500/15">
                        <div>
                            <div class="flex items-start justify-between gap-2">
                                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-violet-50 text-violet-600">
                                    <x-icon name="sparkles" class="h-4 w-4" />
                                </span>
                                <span class="badge badge-purple">Jasa</span>
                            </div>
                            <h4 class="mt-2.5 line-clamp-2 text-[12px] font-bold leading-snug text-slate-800">{{ $s->name }}</h4>
                            <p class="mt-1 font-mono text-[9px] text-slate-400">{{ $s->code }}</p>

                            @if($s->priceTiers->isNotEmpty())
                                <span class="mt-1.5 inline-flex items-center gap-1 rounded-md bg-amber-50 px-1.5 py-0.5 text-[9px] font-bold text-amber-700 ring-1 ring-inset ring-amber-200/70">
                                    <x-icon name="trend-down" class="h-2.5 w-2.5" />
                                    Grosir &ge; 100 lbr
                                </span>
                            @endif
                        </div>
                        <div class="mt-3 flex items-end justify-between border-t border-slate-100 pt-2.5">
                            <div>
                                <p class="text-[9px] font-bold uppercase tracking-[0.1em] text-slate-400">Tarif / {{ $s->unit_name ?? 'Lbr' }}</p>
                                <p class="mt-0.5 text-[13px] font-extrabold text-brand-700 num-tabular">Rp {{ number_format((float) $s->base_price, 0, ',', '.') }}</p>
                            </div>
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-brand-50 text-brand-700 transition group-hover:bg-brand-700 group-hover:text-white">
                                <x-icon name="plus" class="h-4 w-4" />
                            </span>
                        </div>
                    </button>
                @endforeach
            </div>

            @if($retailProducts->isEmpty() && $services->isEmpty())
                <div class="card">
                    <div class="empty-state">
                        <span class="empty-state-icon"><x-icon name="search" class="h-6 w-6" /></span>
                        <p class="text-sm font-bold text-slate-700">Produk tidak ditemukan</p>
                        <p class="mt-1 max-w-xs text-xs leading-relaxed text-slate-400">Coba kata kunci lain atau ubah filter kategori produk.</p>
                        <button wire:click="$set('searchQuery', '')" class="btn btn-secondary btn-sm mt-4">Hapus pencarian</button>
                    </div>
                </div>
            @endif
        </div>

        <!-- Cart -->
        <div class="lg:col-span-4">
            <div class="card flex flex-col overflow-hidden lg:sticky lg:top-[92px] lg:max-h-[calc(100vh-120px)]">
                <div class="shrink-0 border-b border-slate-100 bg-slate-50/50 p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2.5">
                            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-brand-700 text-white shadow-sm">
                                <x-icon name="cart" class="h-4 w-4" />
                            </span>
                            <div>
                                <p class="text-sm font-extrabold text-slate-900">Keranjang</p>
                                <p class="mt-0.5 text-[10px] font-semibold text-slate-400">{{ count($cart) }} item ditambahkan</p>
                            </div>
                        </div>
                        <button wire:click="clearCart" class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-[10px] font-bold text-rose-500 transition hover:bg-rose-50 hover:text-rose-700">
                            <x-icon name="trash" class="h-3.5 w-3.5" />
                            Reset
                        </button>
                    </div>

                    <div class="mt-3">
                        <label class="form-label">Nama Pelanggan</label>
                        <div class="relative">
                            <span class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400">
                                <x-icon name="user" class="h-3.5 w-3.5" />
                            </span>
                            <input type="text" wire:model="customerName" placeholder="Siswa / Guru / Umum"
                                class="input py-2 pl-9 text-xs">
                        </div>
                    </div>
                </div>

                <div class="flex-1 space-y-2 overflow-y-auto p-3.5">
                    @forelse($cart as $index => $item)
                        <div class="rounded-xl border border-slate-200/80 bg-white p-3 shadow-[0_1px_2px_rgba(15,23,42,0.02)]">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="text-[12px] font-bold text-slate-800">{{ $item['name'] }}</span>
                                        <span class="badge {{ $item['type'] === 'RETAIL' ? 'badge-info' : 'badge-purple' }}">{{ $item['type'] === 'RETAIL' ? 'ATK' : 'Jasa' }}</span>
                                    </div>
                                    <p class="mt-1 text-[10px] text-slate-400">Rp {{ number_format($item['base_price'], 0, ',', '.') }} / unit</p>
                                </div>
                                <button wire:click="removeItem({{ $index }})" class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md text-slate-300 transition hover:bg-rose-50 hover:text-rose-500">
                                    <x-icon name="x" class="h-3.5 w-3.5" />
                                </button>
                            </div>

                            @if($item['tier_discount_total'] > 0)
                                <div class="mt-2 flex items-center gap-1.5 rounded-lg bg-amber-50 px-2 py-1 text-[9px] font-bold text-amber-700">
                                    <x-icon name="trend-down" class="h-3 w-3" />
                                    Potongan tier: -Rp {{ number_format($item['tier_discount_total'], 0, ',', '.') }}
                                </div>
                            @endif

                            <div class="mt-2.5 flex items-center justify-between border-t border-slate-100 pt-2.5">
                                <div class="inline-flex items-center overflow-hidden rounded-lg border border-slate-200">
                                    <button wire:click="updateQty({{ $index }}, {{ $item['qty'] - 1 }})"
                                        class="flex h-7 w-7 items-center justify-center bg-slate-50 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">
                                        <x-icon name="minus" class="h-3 w-3" />
                                    </button>
                                    <input type="number" step="1" min="1" value="{{ (int) $item['qty'] }}"
                                        wire:change="updateQty({{ $index }}, $event.target.value)"
                                        class="h-7 w-11 border-x border-slate-200 text-center text-[11px] font-bold text-slate-800 focus:outline-none num-tabular">
                                    <button wire:click="updateQty({{ $index }}, {{ $item['qty'] + 1 }})"
                                        class="flex h-7 w-7 items-center justify-center bg-slate-50 text-slate-500 transition hover:bg-slate-100 hover:text-slate-800">
                                        <x-icon name="plus" class="h-3 w-3" />
                                    </button>
                                </div>

                                <span class="text-[13px] font-extrabold text-slate-900 num-tabular">
                                    Rp {{ number_format($item['subtotal_net'], 0, ',', '.') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="empty-state py-10">
                            <span class="empty-state-icon"><x-icon name="cart" class="h-6 w-6" /></span>
                            <p class="text-sm font-bold text-slate-700">Keranjang masih kosong</p>
                            <p class="mt-1 max-w-[180px] text-[11px] leading-relaxed text-slate-400">Klik produk di katalog untuk menambahkan item ke transaksi.</p>
                        </div>
                    @endforelse
                </div>

                <div class="shrink-0 border-t border-slate-100 bg-slate-50/60 p-4">
                    <div class="space-y-1.5 text-[11px]">
                        <div class="flex items-center justify-between text-slate-500">
                            <span>Subtotal ATK Etalase</span>
                            <span class="font-semibold text-slate-700 num-tabular">Rp {{ number_format($this->subtotalRetail, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex items-center justify-between text-slate-500">
                            <span>Subtotal Layanan Jasa</span>
                            <span class="font-semibold text-slate-700 num-tabular">Rp {{ number_format($this->subtotalService, 0, ',', '.') }}</span>
                        </div>
                        @if($this->totalTierDiscount > 0)
                            <div class="flex items-center justify-between font-semibold text-amber-600">
                                <span>Potongan Grosir Otomatis</span>
                                <span class="num-tabular">-Rp {{ number_format($this->totalTierDiscount, 0, ',', '.') }}</span>
                            </div>
                        @endif

                        <div class="flex items-center justify-between gap-3 pt-1">
                            <span class="text-slate-500">Diskon Manual</span>
                            <div class="relative w-28">
                                <span class="pointer-events-none absolute left-2.5 top-1/2 -translate-y-1/2 text-[10px] font-semibold text-slate-400">Rp</span>
                                <input type="number" step="500" min="0" wire:model.live.debounce.300ms="manualDiscountTotal"
                                    class="input py-1.5 pl-7 text-right text-[11px] font-semibold num-tabular">
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex items-end justify-between border-t border-slate-200/70 pt-3">
                        <div>
                            <p class="text-[9px] font-extrabold uppercase tracking-[0.14em] text-slate-400">Grand Total</p>
                            <p class="mt-1 text-2xl font-extrabold tracking-tight text-brand-700 num-tabular">
                                Rp {{ number_format($this->grandTotal, 0, ',', '.') }}
                            </p>
                        </div>
                        <span class="badge badge-brand">{{ count($cart) }} item</span>
                    </div>

                    <button wire:click="openPaymentModal" @disabled(count($cart) === 0)
                        class="btn btn-success btn-lg mt-3 w-full">
                        <x-icon name="cash" class="h-4 w-4" />
                        Bayar &amp; Cetak Struk
                        <span class="kbd ml-1 border-white/20 bg-white/15 text-white/80">F4</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================================= -->
    <!-- PAYMENT MODAL -->
    <!-- ============================================================= -->
    @if($showPaymentModal)
        <div class="modal-backdrop">
            <div class="modal-card max-w-lg" @click.outside="$wire.set('showPaymentModal', false)">
                <div class="modal-header">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <x-icon name="cash" class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">Pembayaran Kasir</h3>
                            <p class="mt-0.5 text-[11px] text-slate-500">{{ count($cart) }} item &bull; {{ $customerName }}</p>
                        </div>
                    </div>
                    <button wire:click="$set('showPaymentModal', false)" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="modal-body">
                    <div class="rounded-2xl bg-gradient-to-br from-brand-800 to-brand-950 px-5 py-4 text-white">
                        <p class="text-[9px] font-extrabold uppercase tracking-[0.16em] text-white/40">Total Tagihan</p>
                        <p class="mt-1 text-3xl font-extrabold tracking-tight num-tabular">Rp {{ number_format($this->grandTotal, 0, ',', '.') }}</p>
                        <p class="mt-1 text-[10px] text-white/40">Termasuk pajak &amp; potongan yang berlaku</p>
                    </div>

                    <div>
                        <label class="form-label">Metode Pembayaran</label>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" wire:click="$set('paymentMethod', 'CASH')"
                                class="flex items-center justify-center gap-2 rounded-xl border px-3 py-3 text-xs font-bold transition
                                {{ $paymentMethod === 'CASH' ? 'border-brand-500 bg-brand-50 text-brand-800 ring-2 ring-brand-500/15' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                                <x-icon name="cash" class="h-4 w-4" />
                                Tunai
                            </button>
                            <button type="button" wire:click="$set('paymentMethod', 'QRIS')"
                                class="flex items-center justify-center gap-2 rounded-xl border px-3 py-3 text-xs font-bold transition
                                {{ $paymentMethod === 'QRIS' ? 'border-brand-500 bg-brand-50 text-brand-800 ring-2 ring-brand-500/15' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50' }}">
                                <x-icon name="qr" class="h-4 w-4" />
                                QRIS / Bank
                            </button>
                        </div>
                    </div>

                    @if($paymentMethod === 'CASH')
                        <div class="space-y-2.5">
                            <div>
                                <label class="form-label">Uang Diterima (Rp)</label>
                                <input type="number" step="1000" wire:model.live="amountPaid"
                                    class="input py-3 text-lg font-extrabold text-slate-900 num-tabular">
                            </div>

                            <div class="grid grid-cols-4 gap-1.5">
                                <button type="button" wire:click="setQuickCash({{ (int) $this->grandTotal }})" class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-[10px] font-bold text-slate-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700">Uang Pas</button>
                                <button type="button" wire:click="setQuickCash(20000)" class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-[10px] font-bold text-slate-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700">20rb</button>
                                <button type="button" wire:click="setQuickCash(50000)" class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-[10px] font-bold text-slate-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700">50rb</button>
                                <button type="button" wire:click="setQuickCash(100000)" class="rounded-lg border border-slate-200 bg-white px-2 py-2 text-[10px] font-bold text-slate-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700">100rb</button>
                            </div>
                        </div>

                        @php $change = (float) $amountPaid - $this->grandTotal; @endphp
                        <div class="flex items-center justify-between rounded-xl border px-4 py-3
                            {{ $change >= 0 ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
                            <span class="text-[11px] font-bold {{ $change >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                {{ $change >= 0 ? 'Kembalian' : 'Kekurangan' }}
                            </span>
                            <span class="text-lg font-extrabold num-tabular {{ $change >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                Rp {{ number_format(abs($change), 0, ',', '.') }}
                            </span>
                        </div>
                    @else
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5 text-center">
                            <span class="mx-auto flex h-20 w-20 items-center justify-center rounded-2xl bg-white text-slate-700 shadow-sm ring-1 ring-slate-200">
                                <x-icon name="qr" class="h-12 w-12" />
                            </span>
                            <p class="mt-3 text-xs font-bold text-slate-700">Tunjukkan QRIS ke pelanggan</p>
                            <p class="mt-1 text-[11px] leading-relaxed text-slate-400">Penerimaan otomatis dijurnal ke akun Bank Operasional (1-1103).</p>
                            <span class="badge badge-info mt-3">QRIS BESTCO</span>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" wire:click="$set('showPaymentModal', false)" class="btn btn-secondary">Batal <span class="kbd">Esc</span></button>
                    <button type="button" wire:click="executeCheckout" wire:loading.attr="disabled"
                        class="btn btn-success">
                        <x-icon name="check-circle" class="h-4 w-4" />
                        Selesaikan Pembayaran
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================= -->
    <!-- SPOILAGE MODAL -->
    <!-- ============================================================= -->
    @if($showSpoilageModal)
        <div class="modal-backdrop">
            <div class="modal-card max-w-lg" @click.outside="$wire.set('showSpoilageModal', false)">
                <div class="modal-header">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                            <x-icon name="spoilage" class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">Catat Barang Rusak</h3>
                            <p class="mt-0.5 text-[11px] text-slate-500">Gagal produksi &amp; pemotongan bahan baku</p>
                        </div>
                    </div>
                    <button wire:click="$set('showSpoilageModal', false)" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-warning">
                        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span class="leading-relaxed">Kegagalan cetak/fotocopy memotong stok bahan baku kertas dan tinta, lalu otomatis dijurnal ke Beban Kerusakan (6-1101).</span>
                    </div>

                    <div>
                        <label class="form-label">Layanan Jasa yang Gagal</label>
                        <select wire:model="spoilageServiceId" class="select">
                            @foreach($allServices as $srv)
                                <option value="{{ $srv->id }}">{{ $srv->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Jumlah Lembar Gagal</label>
                            <input type="number" step="1" min="1" wire:model="spoilageFailedQty" class="input font-mono">
                        </div>
                        <div>
                            <label class="form-label">Kategori Rusak</label>
                            <select wire:model="spoilageReason" class="select">
                                <option value="PAPER_JAM">Kertas Macet</option>
                                <option value="PRINT_STREAK">Hasil Bergaris</option>
                                <option value="LAMINATION_WRINKLE">Laminating Kusut</option>
                                <option value="OPERATOR_ERROR">Salah Setting</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Deskripsi Kerusakan</label>
                        <input type="text" wire:model="spoilageDescription" placeholder="Contoh: Kertas A4 nyangkut di roller pemanas"
                            class="input">
                    </div>

                    <div>
                        <label class="form-label">PIN Approval Guru Supervisor</label>
                        <input type="password" maxlength="6" wire:model="spoilageTeacherPin" placeholder="6 digit PIN"
                            class="input font-mono tracking-[0.3em]">
                    </div>
                </div>

                <div class="modal-footer">
                    <button wire:click="$set('showSpoilageModal', false)" class="btn btn-secondary">Batal</button>
                    <button wire:click="executeSpoilage" class="btn btn-warning">
                        <x-icon name="check" class="h-4 w-4" />
                        Simpan &amp; Jurnal
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================= -->
    <!-- MUTATION MODAL -->
    <!-- ============================================================= -->
    @if($showMutationModal)
        <div class="modal-backdrop">
            <div class="modal-card max-w-lg" @click.outside="$wire.set('showMutationModal', false)">
                <div class="modal-header">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                            <x-icon name="swap" class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">Mutasi Internal</h3>
                            <p class="mt-0.5 text-[11px] text-slate-500">Ambil bahan baku dari stok etalase</p>
                        </div>
                    </div>
                    <button wire:click="$set('showMutationModal', false)" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="modal-body">
                    <div class="alert alert-info">
                        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
                        <span class="leading-relaxed">Memindahkan stok dari Persediaan Etalase (1-1301) ke Bahan Baku Jasa (1-1302). 1 rim otomatis dikonversi menjadi 500 lembar.</span>
                    </div>

                    <div>
                        <label class="form-label">Aturan Konversi UoM</label>
                        <select wire:model="selectedConversionId" class="select">
                            @foreach($unitConversions as $uc)
                                <option value="{{ $uc->id }}">
                                    1 {{ $uc->sourceProduct->unit?->code }} {{ $uc->sourceProduct->name }} &rarr; {{ (int) $uc->conversion_factor }} Lembar
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Jumlah Rim</label>
                            <input type="number" step="1" min="1" wire:model="mutationSourceQty" class="input font-mono">
                        </div>
                        <div>
                            <label class="form-label">PIN Approval Guru</label>
                            <input type="password" maxlength="6" wire:model="mutationTeacherPin" placeholder="6 digit PIN"
                                class="input font-mono tracking-[0.3em]">
                        </div>
                    </div>

                    <div>
                        <label class="form-label">Catatan Kebutuhan</label>
                        <input type="text" wire:model="mutationNotes" placeholder="Contoh: Kertas fotocopy habis saat jam istirahat"
                            class="input">
                    </div>
                </div>

                <div class="modal-footer">
                    <button wire:click="$set('showMutationModal', false)" class="btn btn-secondary">Batal</button>
                    <button wire:click="executeInternalMutation" class="btn btn-primary">
                        <x-icon name="swap" class="h-4 w-4" />
                        Konversi Stok
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ============================================================= -->
    <!-- RECEIPT MODAL -->
    <!-- ============================================================= -->
    @if($completedTransaction)
        <div class="modal-backdrop">
            <div class="modal-card max-w-sm" @click.outside="$wire.set('completedTransactionId', null)">
                <div class="modal-header">
                    <div class="flex items-center gap-3">
                        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50 text-emerald-600">
                            <x-icon name="check-circle" class="h-5 w-5" />
                        </span>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900">Transaksi Selesai</h3>
                            <p class="mt-0.5 text-[11px] text-slate-500">{{ $completedTransaction->invoice_number }}</p>
                        </div>
                    </div>
                    <button wire:click="$set('completedTransactionId', null)" class="flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-600">
                        <x-icon name="x" class="h-4 w-4" />
                    </button>
                </div>

                <div class="modal-body">
                    <div id="thermal-receipt" class="rounded-xl border border-dashed border-slate-300 bg-white p-4 font-mono text-[10px] leading-relaxed text-slate-900">
                        <div class="text-center">
                            <p class="text-[13px] font-extrabold tracking-[0.08em]">BESTCO</p>
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
                            <div class="flex justify-between font-bold"><span>Grand Total</span><span>Rp {{ number_format((float) $completedTransaction->grand_total, 0, ',', '.') }}</span></div>
                            <div class="flex justify-between text-[9px]"><span>Bayar ({{ $completedTransaction->payment_method->value }})</span><span>Rp {{ number_format((float) $completedTransaction->amount_paid, 0, ',', '.') }}</span></div>
                            <div class="flex justify-between text-[9px]"><span>Kembalian</span><span>Rp {{ number_format((float) $completedTransaction->change_amount, 0, ',', '.') }}</span></div>
                        </div>

                        <div class="mt-3 border-t border-dashed border-slate-300 pt-2 text-center text-[9px] text-slate-500">
                            Terima kasih atas kunjungan Anda<br>
                            Laboratorium Praktik MPLB
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" wire:click="$set('completedTransactionId', null)" class="btn btn-secondary">Selesai</button>
                    <button type="button" onclick="window.print()" class="btn btn-dark">
                        <x-icon name="printer" class="h-4 w-4" />
                        Cetak Struk
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
