<div class="h-full flex flex-col space-y-4"
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

    <!-- Toast Notification -->
    @if($feedbackMessage)
        <div class="p-3 rounded text-xs flex items-center justify-between shadow-sm {{ $feedbackType === 'success' ? 'bg-emerald-50 text-emerald-800 border border-emerald-200' : 'bg-rose-50 text-rose-800 border border-rose-200' }}">
            <span class="font-medium">{{ $feedbackMessage }}</span>
            <button wire:click="$set('feedbackMessage', null)" class="text-slate-400 hover:text-slate-600 font-bold ml-2">&times;</button>
        </div>
    @endif

    <!-- 12-Column POS Workspace -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 flex-1 items-start">

        <!-- ========================================================================= -->
        <!-- LEFT 8 COLS: Search, Categories & Catalog -->
        <!-- ========================================================================= -->
        <div class="lg:col-span-8 flex flex-col space-y-4">
            <!-- Search & Quick Action Bar -->
            <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-sm space-y-3">
                <div class="flex flex-col sm:flex-row items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1 w-full">
                        <input type="text" id="pos-search-input" wire:model.live.debounce.250ms="searchQuery"
                            placeholder="Cari nama barang, barcode, SKU, atau jasa... (Tekan F2)"
                            class="w-full pl-9 pr-3 py-2 text-xs rounded border border-slate-300 focus:outline-none focus:ring-1 focus:ring-brand-600">
                        <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                        @if($searchQuery)
                            <button wire:click="$set('searchQuery', '')" class="absolute right-3 top-2 text-slate-400 hover:text-slate-600 text-xs">&times;</button>
                        @endif
                    </div>

                    <!-- Category Filter Tabs -->
                    <div class="flex items-center gap-1 w-full sm:w-auto overflow-x-auto text-xs">
                        <button wire:click="$set('selectedCategory', 'ALL')"
                            class="px-3 py-2 rounded font-semibold whitespace-nowrap {{ $selectedCategory === 'ALL' ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            Semua
                        </button>
                        <button wire:click="$set('selectedCategory', 'RETAIL')"
                            class="px-3 py-2 rounded font-semibold whitespace-nowrap {{ $selectedCategory === 'RETAIL' ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            ATK Etalase
                        </button>
                        <button wire:click="$set('selectedCategory', 'SERVICE')"
                            class="px-3 py-2 rounded font-semibold whitespace-nowrap {{ $selectedCategory === 'SERVICE' ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
                            Layanan Jasa
                        </button>
                    </div>
                </div>

                <!-- Fast Shortcut Tools Strip -->
                <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-[11px]">
                    <div class="text-slate-500 hidden sm:block">
                        Pintasan: <kbd class="px-1.5 py-0.5 bg-slate-100 border rounded font-mono">F2</kbd> Cari &bull; 
                        <kbd class="px-1.5 py-0.5 bg-slate-100 border rounded font-mono">F4</kbd> Bayar &bull; 
                        <kbd class="px-1.5 py-0.5 bg-slate-100 border rounded font-mono">F6</kbd> Rusak &bull; 
                        <kbd class="px-1.5 py-0.5 bg-slate-100 border rounded font-mono">F7</kbd> Ambil Etalase
                    </div>
                    <div class="flex items-center gap-2">
                        <button wire:click="$set('showSpoilageModal', true)"
                            class="px-2.5 py-1 rounded bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-200 font-medium">
                            ⚠️ [F6] Catat Rusak / Gagal
                        </button>
                        <button wire:click="$set('showMutationModal', true)"
                            class="px-2.5 py-1 rounded bg-indigo-50 hover:bg-indigo-100 text-indigo-800 border border-indigo-200 font-medium">
                            🔄 [F7] Ambil dari Etalase
                        </button>
                    </div>
                </div>
            </div>

            <!-- Product & Service Catalog Grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-4 gap-3">
                <!-- Retail Products -->
                @foreach($retailProducts as $p)
                    <div wire:click="addToCart('RETAIL', {{ $p->id }})"
                        class="bg-white p-3.5 rounded-lg border border-slate-200 hover:border-brand-500 hover:shadow-sm cursor-pointer transition flex flex-col justify-between select-none">
                        <div>
                            <div class="flex items-center justify-between text-[10px] mb-1">
                                <span class="px-1.5 py-0.5 rounded font-mono bg-slate-100 text-slate-600 font-medium">{{ $p->sku }}</span>
                                <span class="px-1.5 py-0.5 rounded font-bold {{ (float) $p->current_stock > 5 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                    Stok: {{ (int) $p->current_stock }} {{ $p->unit?->code }}
                                </span>
                            </div>
                            <h4 class="font-medium text-xs text-slate-800 line-clamp-2">{{ $p->name }}</h4>
                        </div>
                        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[11px] text-slate-400">Harga Jual</span>
                            <span class="font-bold text-xs text-slate-900 num-tabular">Rp {{ number_format((float) $p->selling_price, 0, ',', '.') }}</span>
                        </div>
                    </div>
                @endforeach

                <!-- Services (Fotocopy, Print, Laminating) -->
                @foreach($services as $s)
                    <div wire:click="addToCart('SERVICE', {{ $s->id }})"
                        class="bg-white p-3.5 rounded-lg border border-slate-200 hover:border-brand-500 hover:shadow-sm cursor-pointer transition flex flex-col justify-between select-none">
                        <div>
                            <div class="flex items-center justify-between text-[10px] mb-1">
                                <span class="px-1.5 py-0.5 rounded font-mono bg-blue-50 text-blue-700 font-medium">{{ $s->code }}</span>
                                <span class="px-1.5 py-0.5 rounded font-semibold bg-indigo-50 text-indigo-700">JASA</span>
                            </div>
                            <h4 class="font-medium text-xs text-slate-800 line-clamp-2">{{ $s->name }}</h4>
                            
                            @if($s->priceTiers->isNotEmpty())
                                <div class="mt-1">
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] bg-amber-50 text-amber-800 border border-amber-200 font-medium">
                                        Diskon Grosir &ge; 100 lbr
                                    </span>
                                </div>
                            @endif
                        </div>
                        <div class="mt-3 pt-2 border-t border-slate-100 flex items-center justify-between">
                            <span class="text-[11px] text-slate-400">Tarif / {{ $s->unit_name ?? 'Lbr' }}</span>
                            <span class="font-bold text-xs text-brand-700 num-tabular">Rp {{ number_format((float) $s->base_price, 0, ',', '.') }}</span>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($retailProducts->isEmpty() && $services->isEmpty())
                <div class="bg-white p-8 rounded-lg border border-slate-200 text-center text-xs text-slate-500">
                    Tidak ada produk atau jasa yang cocok dengan kata kunci pencarian.
                </div>
            @endif
        </div>

        <!-- ========================================================================= -->
        <!-- RIGHT 4 COLS: Cart & Billing Summary -->
        <!-- ========================================================================= -->
        <div class="lg:col-span-4 bg-white rounded-lg border border-slate-200 shadow-sm flex flex-col h-full">
            <!-- Cart Header -->
            <div class="p-3.5 border-b border-slate-200 bg-slate-50 rounded-t-lg space-y-2">
                <div class="flex items-center justify-between">
                    <span class="font-bold text-xs text-slate-900 tracking-tight">Keranjang Transaksi POS</span>
                    <button wire:click="clearCart" class="text-[11px] text-rose-600 hover:text-rose-800 font-medium">
                        Reset [Esc]
                    </button>
                </div>
                <div>
                    <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Nama Pelanggan (Siswa/Guru/Umum)</label>
                    <input type="text" wire:model="customerName"
                        class="w-full px-2.5 py-1 text-xs rounded border border-slate-300 bg-white focus:outline-none focus:ring-1 focus:ring-brand-600">
                </div>
            </div>

            <!-- Cart Items List -->
            <div class="flex-1 overflow-y-auto p-3 space-y-2 max-h-[420px]">
                @forelse($cart as $index => $item)
                    <div class="p-2.5 rounded border border-slate-200 bg-white space-y-2 text-xs">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="font-semibold text-slate-800">{{ $item['name'] }}</span>
                                <span class="text-[10px] px-1 rounded ml-1 {{ $item['type'] === 'RETAIL' ? 'bg-slate-100 text-slate-600' : 'bg-blue-50 text-blue-700 font-semibold' }}">
                                    {{ $item['type'] }}
                                </span>
                            </div>
                            <button wire:click="removeItem({{ $index }})" class="text-slate-400 hover:text-rose-600 font-bold text-sm leading-none">&times;</button>
                        </div>

                        <div class="flex items-center justify-between text-[11px] text-slate-500">
                            <span>Harga Satuan: Rp {{ number_format($item['base_price'], 0, ',', '.') }}</span>
                            @if($item['tier_discount_total'] > 0)
                                <span class="text-amber-700 font-medium bg-amber-50 px-1 rounded">
                                    Potongan Tier: -Rp {{ number_format($item['tier_discount_total'], 0, ',', '.') }}
                                </span>
                            @endif
                        </div>

                        <div class="flex items-center justify-between pt-1 border-t border-slate-100">
                            <!-- Qty Adjuster -->
                            <div class="flex items-center border border-slate-300 rounded overflow-hidden">
                                <button wire:click="updateQty({{ $index }}, {{ $item['qty'] - 1 }})" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 font-bold">-</button>
                                <input type="number" step="1" min="1" value="{{ (int) $item['qty'] }}"
                                    wire:change="updateQty({{ $index }}, $event.target.value)"
                                    class="w-14 text-center text-xs py-0.5 focus:outline-none num-tabular">
                                <button wire:click="updateQty({{ $index }}, {{ $item['qty'] + 1 }})" class="px-2 py-0.5 bg-slate-100 hover:bg-slate-200 font-bold">+</button>
                            </div>

                            <span class="font-bold text-slate-900 num-tabular">
                                Rp {{ number_format($item['subtotal_net'], 0, ',', '.') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-xs text-slate-400">
                        <div class="text-2xl mb-1">🛒</div>
                        Keranjang masih kosong.<br>Klik item di katalog untuk menambahkan.
                    </div>
                @endforelse
            </div>

            <!-- Financial Summary Box -->
            <div class="p-4 border-t border-slate-200 bg-slate-50 space-y-2 text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal ATK Etalase</span>
                    <span class="num-tabular font-medium">Rp {{ number_format($this->subtotalRetail, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal Layanan Jasa (Bruto)</span>
                    <span class="num-tabular font-medium">Rp {{ number_format($this->subtotalService, 0, ',', '.') }}</span>
                </div>
                @if($this->totalTierDiscount > 0)
                    <div class="flex justify-between text-amber-700 font-medium">
                        <span>Potongan Grosir Otomatis (Tier)</span>
                        <span class="num-tabular">-Rp {{ number_format($this->totalTierDiscount, 0, ',', '.') }}</span>
                    </div>
                @endif

                <div class="flex items-center justify-between text-slate-600 pt-1">
                    <span>Diskon Manual Kasir (Rp)</span>
                    <input type="number" step="500" min="0" wire:model.live.debounce.300ms="manualDiscountTotal"
                        class="w-24 text-right px-2 py-0.5 text-xs rounded border border-slate-300 num-tabular">
                </div>

                <div class="pt-2 border-t border-slate-200 flex justify-between items-baseline">
                    <span class="font-bold text-slate-900 text-sm">Grand Total</span>
                    <span class="font-bold text-brand-700 text-lg num-tabular">
                        Rp {{ number_format($this->grandTotal, 0, ',', '.') }}
                    </span>
                </div>

                <button wire:click="openPaymentModal"
                    class="w-full mt-2 py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded transition shadow-sm flex items-center justify-center gap-1.5">
                    <span>Bayar & Cetak Struk [F4]</span>
                    <span>&rarr;</span>
                </button>
            </div>
        </div>
    </div>

    <!-- ========================================================================= -->
    <!-- MODAL: PEMBAYARAN KASIR (F4 / F9) -->
    <!-- ========================================================================= -->
    @if($showPaymentModal)
        <div class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-lg border border-slate-200 max-w-md w-full p-6 space-y-5 shadow-lg text-xs" @click.outside="$wire.set('showPaymentModal', false)">
                <div class="flex items-center justify-between border-b pb-3">
                    <h3 class="font-bold text-sm text-slate-900">Pembayaran Kasir POS</h3>
                    <button wire:click="$set('showPaymentModal', false)" class="text-slate-400 hover:text-slate-600 text-lg">&times;</button>
                </div>

                <div class="p-3 bg-brand-50 border border-brand-200 rounded text-center">
                    <span class="text-slate-500 block text-xs">Total Tagihan Transaksi</span>
                    <span class="text-xl font-bold text-brand-800 num-tabular">
                        Rp {{ number_format($this->grandTotal, 0, ',', '.') }}
                    </span>
                </div>

                <!-- Payment Method Toggle -->
                <div>
                    <label class="block font-medium text-slate-700 mb-1">Metode Pembayaran</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" wire:click="$set('paymentMethod', 'CASH')"
                            class="py-2 px-3 rounded font-semibold border text-center {{ $paymentMethod === 'CASH' ? 'bg-brand-700 text-white border-brand-700' : 'bg-white text-slate-700 border-slate-300' }}">
                            💵 Tunai (Kas Laci)
                        </button>
                        <button type="button" wire:click="$set('paymentMethod', 'QRIS')"
                            class="py-2 px-3 rounded font-semibold border text-center {{ $paymentMethod === 'QRIS' ? 'bg-brand-700 text-white border-brand-700' : 'bg-white text-slate-700 border-slate-300' }}">
                            📱 QRIS / Bank TeFa
                        </button>
                    </div>
                </div>

                @if($paymentMethod === 'CASH')
                    <!-- Amount Paid & Quick Buttons -->
                    <div class="space-y-2">
                        <label class="block font-medium text-slate-700">Jumlah Uang Diterima (Rp)</label>
                        <input type="number" step="1000" wire:model.live="amountPaid"
                            class="w-full px-3 py-2 text-base font-bold text-slate-900 rounded border border-slate-300 font-mono focus:outline-none focus:ring-1 focus:ring-brand-600">
                        
                        <!-- Quick Cash Pills -->
                        <div class="flex gap-1.5 overflow-x-auto text-[11px]">
                            <button type="button" wire:click="setQuickCash({{ (int) $this->grandTotal }})" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 rounded font-medium">Uang Pas</button>
                            <button type="button" wire:click="setQuickCash(20000)" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 rounded font-medium">20k</button>
                            <button type="button" wire:click="setQuickCash(50000)" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 rounded font-medium">50k</button>
                            <button type="button" wire:click="setQuickCash(100000)" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 rounded font-medium">100k</button>
                        </div>
                    </div>

                    <!-- Change Calculation -->
                    @php
                        $change = (float) $amountPaid - $this->grandTotal;
                    @endphp
                    <div class="p-3 bg-slate-50 border rounded flex justify-between items-center">
                        <span class="text-slate-600 font-medium">Kembalian:</span>
                        <span class="font-bold text-base num-tabular {{ $change >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                            {{ $change >= 0 ? 'Rp ' . number_format($change, 0, ',', '.') : 'Kurang Rp ' . number_format(abs($change), 0, ',', '.') }}
                        </span>
                    </div>
                @else
                    <div class="p-4 bg-slate-50 rounded border text-center space-y-2">
                        <span class="text-xs text-slate-600 font-medium">Tunjukkan kode QRIS Dinamis ke Pelanggan</span>
                        <div class="w-36 h-36 bg-white border border-slate-300 mx-auto flex items-center justify-center font-mono text-[10px] text-slate-400">
                            [QRIS BESTCO]
                        </div>
                        <span class="text-[11px] text-slate-500 block">Penerimaan otomatis dijurnal ke akun Bank Operasional (1-1103)</span>
                    </div>
                @endif

                <div class="pt-3 border-t flex justify-end gap-2">
                    <button type="button" wire:click="$set('showPaymentModal', false)" class="px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold">
                        Batal [Esc]
                    </button>
                    <button type="button" wire:click="executeCheckout"
                        class="px-5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded font-bold shadow-sm">
                        Selesaikan & Cetak Struk &rarr;
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- MODAL: CATAT BARANG RUSAK / GAGAL FOTOCOPY (F6) -->
    <!-- ========================================================================= -->
    @if($showSpoilageModal)
        <div class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-lg border border-slate-200 max-w-md w-full p-6 space-y-4 shadow-lg text-xs" @click.outside="$wire.set('showSpoilageModal', false)">
                <div class="flex items-center justify-between border-b pb-2">
                    <h3 class="font-bold text-sm text-slate-900">Catat Barang Rusak / Gagal Produksi [F6]</h3>
                    <button wire:click="$set('showSpoilageModal', false)" class="text-slate-400 text-lg">&times;</button>
                </div>

                <p class="text-slate-500 text-[11px]">
                    Kegagalan cetak/fotocopy tetap memotong stok bahan baku kertas dan kuota tinta (0.0500 g/lembar) serta otomatis dijurnal ke Beban Kerusakan (6-1101).
                </p>

                <div class="space-y-3">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Layanan Jasa yang Gagal</label>
                        <select wire:model="spoilageServiceId" class="w-full px-3 py-1.5 rounded border border-slate-300 bg-white">
                            @foreach($allServices as $srv)
                                <option value="{{ $srv->id }}">{{ $srv->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block font-medium text-slate-700 mb-1">Jumlah Lembar Gagal</label>
                            <input type="number" step="1" min="1" wire:model="spoilageFailedQty"
                                class="w-full px-3 py-1.5 rounded border border-slate-300 font-mono">
                        </div>
                        <div>
                            <label class="block font-medium text-slate-700 mb-1">Kategori Rusak</label>
                            <select wire:model="spoilageReason" class="w-full px-3 py-1.5 rounded border border-slate-300 bg-white">
                                <option value="PAPER_JAM">Kertas Macet (Paper Jam)</option>
                                <option value="PRINT_STREAK">Hasil Bergaris / Pudar</option>
                                <option value="LAMINATION_WRINKLE">Laminating Kusut</option>
                                <option value="OPERATOR_ERROR">Salah Setting Ukuran</option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Deskripsi Kerusakan</label>
                        <input type="text" wire:model="spoilageDescription" placeholder="Contoh: Kertas A4 nyangkut di roller pemanas"
                            class="w-full px-3 py-1.5 rounded border border-slate-300">
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">PIN Approval Guru Supervisor</label>
                        <input type="password" maxlength="6" wire:model="spoilageTeacherPin" placeholder="6 digit PIN"
                            class="w-full px-3 py-1.5 rounded border border-slate-300 font-mono">
                    </div>
                </div>

                <div class="pt-3 border-t flex justify-end gap-2">
                    <button wire:click="$set('showSpoilageModal', false)" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold">
                        Batal
                    </button>
                    <button wire:click="executeSpoilage" class="px-4 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded font-bold">
                        Simpan Barang Rusak & Jurnal
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- MODAL: MUTASI INTERNAL "AMBIL DARI ETALASE" (F7) -->
    <!-- ========================================================================= -->
    @if($showMutationModal)
        <div class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-lg border border-slate-200 max-w-md w-full p-6 space-y-4 shadow-lg text-xs" @click.outside="$wire.set('showMutationModal', false)">
                <div class="flex items-center justify-between border-b pb-2">
                    <h3 class="font-bold text-sm text-slate-900">Mutasi Internal: Ambil dari Etalase [F7]</h3>
                    <button wire:click="$set('showMutationModal', false)" class="text-slate-400 text-lg">&times;</button>
                </div>

                <p class="text-slate-500 text-[11px]">
                    Memindahkan kertas dari Persediaan Etalase (1-1301) ke Persediaan Bahan Baku Jasa (1-1302). 1 Rim otomatis dikonversi menjadi 500 Lembar.
                </p>

                <div class="space-y-3">
                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Aturan Konversi UoM</label>
                        <select wire:model="selectedConversionId" class="w-full px-3 py-1.5 rounded border border-slate-300 bg-white">
                            @foreach($unitConversions as $uc)
                                <option value="{{ $uc->id }}">
                                    1 {{ $uc->sourceProduct->unit?->code }} {{ $uc->sourceProduct->name }} &rarr; {{ (int) $uc->conversion_factor }} Lembar Bahan Jasa
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Jumlah Rim yang Diambil</label>
                        <input type="number" step="1" min="1" wire:model="mutationSourceQty"
                            class="w-full px-3 py-1.5 rounded border border-slate-300 font-mono">
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">Catatan Kebutuhan</label>
                        <input type="text" wire:model="mutationNotes" placeholder="Contoh: Kertas fotocopy A4 habis saat jam istirahat"
                            class="w-full px-3 py-1.5 rounded border border-slate-300">
                    </div>

                    <div>
                        <label class="block font-medium text-slate-700 mb-1">PIN Approval Guru Supervisor</label>
                        <input type="password" maxlength="6" wire:model="mutationTeacherPin" placeholder="6 digit PIN"
                            class="w-full px-3 py-1.5 rounded border border-slate-300 font-mono">
                    </div>
                </div>

                <div class="pt-3 border-t flex justify-end gap-2">
                    <button wire:click="$set('showMutationModal', false)" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold">
                        Batal
                    </button>
                    <button wire:click="executeInternalMutation" class="px-4 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded font-bold">
                        Konversi Stok & Jurnal
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- ========================================================================= -->
    <!-- MODAL: CETAK STRUK THERMAL (58mm / 80mm) -->
    <!-- ========================================================================= -->
    @if($completedTransaction)
        <div class="fixed inset-0 z-50 bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-lg border border-slate-200 max-w-sm w-full p-5 space-y-4 shadow-xl text-xs" @click.outside="$wire.set('completedTransactionId', null)">
                <div class="flex items-center justify-between border-b pb-2">
                    <h3 class="font-bold text-sm text-slate-900">Struk Pembayaran Transaksi</h3>
                    <button wire:click="$set('completedTransactionId', null)" class="text-slate-400 text-lg">&times;</button>
                </div>

                <!-- Printable Receipt Area (58mm/80mm) -->
                <div id="thermal-receipt" class="p-3 bg-white text-slate-900 font-mono text-[11px] leading-tight space-y-2 border border-dashed border-slate-300 rounded">
                    <div class="text-center space-y-0.5">
                        <div class="font-bold text-xs">BESTCO TeFa MPLB</div>
                        <div class="text-[10px]">SMK Teaching Factory Business Center</div>
                        <div class="text-[10px] border-b pb-1">No: {{ $completedTransaction->invoice_number }}</div>
                    </div>

                    <div class="flex justify-between text-[10px]">
                        <span>Tgl: {{ $completedTransaction->transaction_date->format('d/m/y H:i') }}</span>
                        <span>Kasir: {{ $completedTransaction->cashier->name }}</span>
                    </div>
                    <div class="text-[10px]">Pelanggan: {{ $completedTransaction->customer_name }}</div>

                    <div class="border-t border-b border-dashed py-1.5 space-y-1">
                        @foreach($completedTransaction->items as $it)
                            <div>
                                <div class="font-bold">{{ $it->item_name_snapshot }}</div>
                                <div class="flex justify-between text-[10px]">
                                    <span>{{ (int) $it->qty }} x {{ number_format((float) $it->base_unit_price, 0, ',', '.') }}</span>
                                    <span>{{ number_format((float) $it->line_net_total, 0, ',', '.') }}</span>
                                </div>
                                @if((float) $it->line_total_discount > 0)
                                    <div class="text-[9px] text-right text-slate-500">
                                        (Diskon: -{{ number_format((float) $it->line_total_discount, 0, ',', '.') }})
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="space-y-0.5 pt-1 text-[10px]">
                        <div class="flex justify-between">
                            <span>Grand Total:</span>
                            <span class="font-bold">Rp {{ number_format((float) $completedTransaction->grand_total, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Bayar ({{ $completedTransaction->payment_method->value }}):</span>
                            <span>Rp {{ number_format((float) $completedTransaction->amount_paid, 0, ',', '.') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Kembalian:</span>
                            <span>Rp {{ number_format((float) $completedTransaction->change_amount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    <div class="text-center pt-2 border-t border-dashed text-[10px] text-slate-500">
                        Terima Kasih Atas Kunjungan Anda<br>
                        Laboratorium Praktik MPLB
                    </div>
                </div>

                <div class="flex gap-2 justify-end pt-2 border-t">
                    <button type="button" onclick="window.print()" class="px-4 py-1.5 bg-slate-900 hover:bg-slate-800 text-white rounded font-bold text-xs">
                        🖨️ Cetak Struk
                    </button>
                    <button type="button" wire:click="$set('completedTransactionId', null)" class="px-4 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded font-semibold text-xs">
                        Selesai
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>

