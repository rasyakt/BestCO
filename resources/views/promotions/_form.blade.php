@php
    $isEdit = $promotion !== null;
    $actionUrl = $isEdit ? route('promotions.update', $promotion) : route('promotions.store');
    $currentScope = old('scope', $promotion?->scope?->value ?? 'ALL_ITEMS');
    $currentDiscountType = old('discount_type', $promotion?->discount_type?->value ?? 'PERCENTAGE');
    $startVal = old('start_datetime', $promotion?->start_datetime?->format('Y-m-d\TH:i') ?? now()->format('Y-m-d\TH:i'));
    $endVal = old('end_datetime', $promotion?->end_datetime?->format('Y-m-d\TH:i') ?? now()->addDays(7)->format('Y-m-d\TH:i'));
@endphp

<form method="POST" action="{{ $actionUrl }}" class="space-y-6" x-data="{
    scope: '{{ $currentScope }}',
    discountType: '{{ $currentDiscountType }}'
}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <x-icon name="x-circle" class="mt-0.5 h-4 w-4 shrink-0" />
            <div>
                <p class="font-semibold">Periksa kembali data promo:</p>
                <ul class="mt-1 list-inside list-disc space-y-0.5 text-xs">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 items-start gap-6 xl:grid-cols-3">
        <!-- Kolom Kiri: Form Utama (2 Kolom) -->
        <div class="space-y-6 xl:col-span-2">
            <!-- Kartu 1: Identitas & Informasi Utama Promo -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Informasi Utama Promo</h2>
                        <p class="card-subtitle">Nama program dan kode kupon pengenal transaksi kasir.</p>
                    </div>
                </div>

                <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Nama Promo -->
                    <div class="sm:col-span-2">
                        <label for="name" class="form-label">Nama Program Promo <span class="text-rose-500">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $promotion?->name) }}" required
                            placeholder="Contoh: Promo Hardiknas Diskon 10%"
                            class="input @error('name') border-rose-300 @enderror">
                        @error('name')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <!-- Kode Kupon / Voucher (Opsional) -->
                    <div class="sm:col-span-2">
                        <label for="code" class="form-label">Kode Kupon / Voucher <span class="text-slate-400 font-normal">(Opsional)</span></label>
                        <input type="text" id="code" name="code" value="{{ old('code', $promotion?->code) }}"
                            placeholder="Contoh: HARDIKNAS10"
                            class="input uppercase placeholder:normal-case font-mono @error('code') border-rose-300 @enderror">
                        <p class="mt-1 text-[11px] text-slate-400">Jika diisi, kasir dapat memasukkan kode ini. Jika dikosongkan, promo berlaku <strong>otomatis</strong> saat syarat belanja terpenuhi.</p>
                        @error('code')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <!-- Deskripsi Promo -->
                    <div class="sm:col-span-2">
                        <label for="description" class="form-label">Deskripsi / Catatan Promo <span class="text-slate-400 font-normal">(Opsional)</span></label>
                        <textarea id="description" name="description" rows="2"
                            placeholder="Contoh: Berlaku untuk seluruh siswa dan staf selama pekan ujian sekolah."
                            class="input resize-none @error('description') border-rose-300 @enderror">{{ old('description', $promotion?->description) }}</textarea>
                        @error('description')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- Kartu 2: Cakupan Target & Skema Potongan -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Cakupan Target & Nilai Potongan</h2>
                        <p class="card-subtitle">Aturan produk sasaran dan kalkulasi potongan harga.</p>
                    </div>
                </div>

                <div class="card-body grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Cakupan (Scope) -->
                    <div>
                        <label for="scope" class="form-label">Cakupan Target Promo <span class="text-rose-500">*</span></label>
                        <select id="scope" name="scope" x-model="scope" class="select @error('scope') border-rose-300 @enderror">
                            <option value="ALL_ITEMS">Semua Produk & Layanan Jasa</option>
                            <option value="CATEGORY">Kategori Produk Tertentu</option>
                            <option value="PRODUCT">Produk Ritel Tertentu</option>
                            <option value="SERVICE">Layanan Jasa Tertentu</option>
                            <option value="MIN_SPEND">Minimal Total Belanja Keseluruhan</option>
                        </select>
                        @error('scope')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <!-- Pilihan Target Dinamis Berdasarkan Scope -->
                    <div>
                        <!-- Dropdown Kategori -->
                        <div x-show="scope === 'CATEGORY'" x-cloak>
                            <label for="category_id" class="form-label">Pilih Kategori <span class="text-rose-500">*</span></label>
                            <select id="category_id" name="category_id" class="select @error('category_id') border-rose-300 @enderror">
                                <option value="">-- Pilih Kategori Target --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" @selected(old('category_id', $promotion?->category_id) == $cat->id)>
                                        {{ $cat->name }} ({{ $cat->code }})
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <!-- Dropdown Produk Ritel -->
                        <div x-show="scope === 'PRODUCT'" x-cloak>
                            <label for="product_id" class="form-label">Pilih Produk Ritel <span class="text-rose-500">*</span></label>
                            <select id="product_id" name="product_id" class="select @error('product_id') border-rose-300 @enderror">
                                <option value="">-- Pilih Produk Ritel --</option>
                                @foreach($products as $prod)
                                    <option value="{{ $prod->id }}" @selected(old('product_id', $promotion?->product_id) == $prod->id)>
                                        {{ $prod->name }} — Rp {{ number_format((float) $prod->selling_price, 0, ',', '.') }}
                                    </option>
                                @endforeach
                            </select>
                            @error('product_id')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <!-- Dropdown Layanan Jasa -->
                        <div x-show="scope === 'SERVICE'" x-cloak>
                            <label for="service_id" class="form-label">Pilih Layanan Jasa <span class="text-rose-500">*</span></label>
                            <select id="service_id" name="service_id" class="select @error('service_id') border-rose-300 @enderror">
                                <option value="">-- Pilih Layanan Jasa --</option>
                                @foreach($services as $srv)
                                    <option value="{{ $srv->id }}" @selected(old('service_id', $promotion?->service_id) == $srv->id)>
                                        {{ $srv->name }} — Rp {{ number_format((float) $srv->base_price, 0, ',', '.') }}/{{ $srv->base_unit_name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('service_id')<p class="form-error">{{ $message }}</p>@enderror
                        </div>

                        <!-- Keterangan Umum jika ALL_ITEMS atau MIN_SPEND -->
                        <div x-show="scope === 'ALL_ITEMS' || scope === 'MIN_SPEND'" x-cloak>
                            <label class="form-label">Keterangan Target</label>
                            <p class="text-[11px] text-slate-500 rounded-md border border-slate-200 bg-slate-50 p-2.5">
                                Diskon berlaku untuk seluruh item belanja yang memenuhi syarat di kasir.
                            </p>
                        </div>
                    </div>

                    <!-- Tipe Diskon -->
                    <div>
                        <label for="discount_type" class="form-label">Jenis Potongan <span class="text-rose-500">*</span></label>
                        <select id="discount_type" name="discount_type" x-model="discountType" class="select @error('discount_type') border-rose-300 @enderror">
                            <option value="PERCENTAGE">Persentase (%)</option>
                            <option value="FIXED_AMOUNT">Potongan Nominal (Rp)</option>
                        </select>
                        @error('discount_type')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <!-- Nilai Diskon -->
                    <div>
                        <label for="discount_value" class="form-label">
                            Besar Potongan <span class="text-rose-500">*</span> <span class="text-slate-400 font-normal" x-text="discountType === 'PERCENTAGE' ? '(%)' : '(Rp)'"></span>
                        </label>
                        <div class="relative">
                            <span x-show="discountType === 'FIXED_AMOUNT'" class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-slate-400">Rp</span>
                            <input type="number" step="0.01" min="0.01" id="discount_value" name="discount_value"
                                value="{{ old('discount_value', $promotion ? (float) $promotion->discount_value : '') }}" required
                                placeholder="Contoh: 10 atau 5000"
                                :class="discountType === 'FIXED_AMOUNT' ? 'pl-8' : ''"
                                class="input num-tabular @error('discount_value') border-rose-300 @enderror">
                        </div>
                        @error('discount_value')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <!-- Maksimal Potongan (Khusus Persentase) -->
                    <div x-show="discountType === 'PERCENTAGE'" x-cloak>
                        <label for="max_discount_amount" class="form-label">Batas Maksimal Diskon <span class="text-slate-400 font-normal">(Opsional)</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-slate-400">Rp</span>
                            <input type="number" step="500" min="0" id="max_discount_amount" name="max_discount_amount"
                                value="{{ old('max_discount_amount', $promotion ? (float) $promotion->max_discount_amount : '') }}"
                                placeholder="Contoh: 15000 (kosongkan jika tanpa batas)"
                                class="input pl-8 num-tabular @error('max_discount_amount') border-rose-300 @enderror">
                        </div>
                        <p class="mt-1 text-[10px] text-slate-400">Batas atas rupiah agar diskon persentase tidak melebihi batas wajar.</p>
                        @error('max_discount_amount')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <!-- Minimal Belanja Kotor -->
                    <div>
                        <label for="min_order_amount" class="form-label">Syarat Minimal Belanja <span class="text-slate-400 font-normal">(Opsional)</span></label>
                        <div class="relative">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-xs text-slate-400">Rp</span>
                            <input type="number" step="1000" min="0" id="min_order_amount" name="min_order_amount"
                                value="{{ old('min_order_amount', $promotion ? (float) $promotion->min_order_amount : '0') }}"
                                placeholder="Contoh: 20000 (0 jika tanpa syarat)"
                                class="input pl-8 num-tabular @error('min_order_amount') border-rose-300 @enderror">
                        </div>
                        <p class="mt-1 text-[10px] text-slate-400">Promo hanya aktif jika nilai keranjang belanja mencapai nominal ini.</p>
                        @error('min_order_amount')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Kolom Kanan: Jadwal & Status (1 Kolom) -->
        <div class="space-y-6">
            <!-- Kartu 3: Rentang Waktu (Jadwal) -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Jadwal Periode</h2>
                        <p class="card-subtitle">Rentang tanggal & jam aktif promo.</p>
                    </div>
                </div>

                <div class="card-body space-y-4">
                    <!-- Waktu Mulai -->
                    <div>
                        <label for="start_datetime" class="form-label">Waktu Mulai <span class="text-rose-500">*</span></label>
                        <input type="datetime-local" id="start_datetime" name="start_datetime" value="{{ $startVal }}" required
                            class="input @error('start_datetime') border-rose-300 @enderror">
                        <p class="mt-1 text-[11px] text-slate-400">Mulai berlaku otomatis di POS.</p>
                        @error('start_datetime')<p class="form-error">{{ $message }}</p>@enderror
                    </div>

                    <!-- Waktu Berakhir -->
                    <div>
                        <label for="end_datetime" class="form-label">Waktu Berakhir <span class="text-rose-500">*</span></label>
                        <input type="datetime-local" id="end_datetime" name="end_datetime" value="{{ $endVal }}" required
                            class="input @error('end_datetime') border-rose-300 @enderror">
                        <p class="mt-1 text-[11px] text-slate-400">Otomatis kedaluwarsa setelah waktu ini.</p>
                        @error('end_datetime')<p class="form-error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <!-- Kartu 4: Status Aktivasi -->
            <div class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Status Program</h2>
                        <p class="card-subtitle">Kontrol aktivasi saklar promo.</p>
                    </div>
                </div>

                <div class="card-body space-y-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $promotion?->is_active ?? true))
                            class="mt-0.5 h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <div>
                            <span class="text-xs font-semibold text-slate-800">Aktifkan promo ini</span>
                            <p class="text-[11px] text-slate-400 mt-0.5">Bila dicentang, promo akan otomatis bekerja saat rentang jadwal aktif tiba.</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Tombol Aksi Simpan -->
            <div class="flex items-center gap-2">
                <button type="submit" class="btn btn-primary flex-1">
                    <x-icon name="check" class="h-4 w-4" />
                    <span>{{ $isEdit ? 'Simpan Perubahan' : 'Buat Program Promo' }}</span>
                </button>
                <a href="{{ route('promotions.index') }}" class="btn btn-secondary">
                    Batal
                </a>
            </div>
        </div>
    </div>
</form>
