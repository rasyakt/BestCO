@extends('layouts.app', ['title' => 'Pengaturan Informasi Toko — BESTCO'])

@section('content')
<div class="container mx-auto max-w-4xl px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <a href="{{ route('dashboard') }}" class="hover:text-brand-600">Dashboard</a>
            <x-icon name="chevron-right" class="h-3 w-3" />
            <span class="font-medium text-slate-700">Pengaturan Toko</span>
        </div>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Pengaturan Informasi Toko</h1>
        <p class="mt-1 text-sm text-slate-500">Kelola informasi toko yang tampil di struk pembayaran</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Form Settings -->
        <div class="lg:col-span-2">
            <form action="{{ route('settings.store.update') }}" method="POST" class="card">
                @csrf
                @method('PUT')
                
                <div class="card-header">
                    <h2 class="text-base font-semibold text-slate-900">Informasi Toko</h2>
                    <p class="mt-1 text-xs text-slate-500">Data ini akan tampil di struk pembayaran</p>
                </div>
                
                <div class="card-body space-y-4">
                    <!-- Nama Toko -->
                    <div>
                        <label class="form-label">Nama Toko <span class="text-rose-500">*</span></label>
                        <input type="text" name="store_name" 
                            value="{{ old('store_name', $storeInfo['store_name']) }}" 
                            placeholder="Contoh: BESTCO" 
                            class="input" 
                            required>
                        @error('store_name')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-slate-500">Tampil di header struk (bold, besar)</p>
                    </div>

                    <!-- Tagline -->
                    <div>
                        <label class="form-label">Tagline / Subjudul</label>
                        <input type="text" name="store_tagline" 
                            value="{{ old('store_tagline', $storeInfo['store_tagline']) }}" 
                            placeholder="Contoh: Teaching Factory MPLB" 
                            class="input">
                        @error('store_tagline')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-slate-500">Tampil di bawah nama toko</p>
                    </div>

                    <!-- Alamat Baris 1 -->
                    <div>
                        <label class="form-label">Alamat Baris 1</label>
                        <input type="text" name="store_address_line1" 
                            value="{{ old('store_address_line1', $storeInfo['store_address_line1']) }}" 
                            placeholder="Contoh: SMK Business Center" 
                            class="input">
                        @error('store_address_line1')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Alamat Baris 2 -->
                    <div>
                        <label class="form-label">Alamat Baris 2</label>
                        <input type="text" name="store_address_line2" 
                            value="{{ old('store_address_line2', $storeInfo['store_address_line2']) }}" 
                            placeholder="Contoh: Jl. Pendidikan No. 123" 
                            class="input">
                        @error('store_address_line2')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Telepon & Email -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label">Telepon</label>
                            <input type="tel" name="store_phone" 
                                value="{{ old('store_phone', $storeInfo['store_phone']) }}" 
                                placeholder="0812-3456-7890" 
                                class="input">
                            @error('store_phone')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="form-label">Email</label>
                            <input type="email" name="store_email" 
                                value="{{ old('store_email', $storeInfo['store_email']) }}" 
                                placeholder="info@bestco.sch.id" 
                                class="input">
                            @error('store_email')
                                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="border-t border-slate-200 pt-4">
                        <h3 class="text-sm font-semibold text-slate-900">Footer Struk</h3>
                        <p class="mt-1 text-xs text-slate-500">Teks yang tampil di bagian bawah struk</p>
                    </div>

                    <!-- Footer Baris 1 -->
                    <div>
                        <label class="form-label">Footer Baris 1</label>
                        <input type="text" name="receipt_footer_line1" 
                            value="{{ old('receipt_footer_line1', $storeInfo['receipt_footer_line1']) }}" 
                            placeholder="Contoh: Terima kasih" 
                            class="input">
                        @error('receipt_footer_line1')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Footer Baris 2 -->
                    <div>
                        <label class="form-label">Footer Baris 2</label>
                        <input type="text" name="receipt_footer_line2" 
                            value="{{ old('receipt_footer_line2', $storeInfo['receipt_footer_line2']) }}" 
                            placeholder="Contoh: Laboratorium Praktik MPLB" 
                            class="input">
                        @error('receipt_footer_line2')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="card-footer flex flex-col gap-2 sm:flex-row">
                    <button type="submit" class="btn btn-primary flex-1">
                        <x-icon name="check" class="h-4 w-4" />
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary flex-1">
                        Kembali
                    </a>
                </div>
            </form>

            <!-- Reset Form -->
            <form action="{{ route('settings.store.reset') }}" method="POST"
                x-data
                @submit.prevent="window.bestcoDialog.confirm({
                    title: 'Reset Pengaturan Toko?',
                    message: 'Seluruh identitas dan pengaturan toko akan dikembalikan ke nilai default.',
                    detail: 'Logo kustom dan perubahan pengaturan sebelumnya akan diganti. Tindakan ini tidak dapat dibatalkan.',
                    type: 'warning',
                    confirmText: 'Ya, Reset',
                    cancelText: 'Batal',
                    onConfirm: () => $el.submit()
                })"
                class="mt-4">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-secondary w-full text-sm">
                    <x-icon name="refresh" class="h-4 w-4" />
                    Reset ke Default
                </button>
            </form>
        </div>

        <!-- Preview Struk -->
        <div class="lg:col-span-1">
            <div class="card sticky top-4">
                <div class="card-header">
                    <h2 class="text-base font-semibold text-slate-900">Preview Struk</h2>
                    <p class="mt-1 text-xs text-slate-500">Tampilan di struk pembayaran</p>
                </div>
                
                <div class="card-body">
                    <div class="rounded-lg border-2 border-dashed border-slate-300 bg-white p-4 font-mono text-[10px] leading-relaxed text-slate-900">
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
                            <p class="mt-2 border-y border-dashed border-slate-300 py-1 text-[9px]">No: INV-20261101-0001</p>
                        </div>

                        <div class="mt-2 space-y-0.5 text-[9px]">
                            <div class="flex justify-between"><span>Tgl</span><span>01/11/26 10:30</span></div>
                            <div class="flex justify-between"><span>Kasir</span><span>Admin</span></div>
                            <div class="flex justify-between"><span>Pelanggan</span><span>Umum</span></div>
                        </div>

                        <div class="mt-2 space-y-1.5 border-y border-dashed border-slate-300 py-2">
                            <div>
                                <p class="font-bold">Fotocopy A4</p>
                                <div class="flex justify-between text-[9px] text-slate-500">
                                    <span>10 x 300</span>
                                    <span class="text-slate-800">3.000</span>
                                </div>
                            </div>
                        </div>

                        <div class="mt-2 space-y-0.5 text-[9px]">
                            <div class="flex justify-between">
                                <span>Subtotal</span>
                                <span>Rp 3.000</span>
                            </div>
                        </div>

                        <div class="mt-1.5 space-y-0.5 border-t border-dashed border-slate-300 pt-1.5">
                            <div class="flex justify-between font-bold text-[11px]">
                                <span>TOTAL</span>
                                <span>Rp 3.000</span>
                            </div>
                            <div class="flex justify-between text-[9px]">
                                <span>Bayar (CASH)</span>
                                <span>Rp 5.000</span>
                            </div>
                            <div class="flex justify-between text-[9px]">
                                <span>Kembalian</span>
                                <span>Rp 2.000</span>
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
            </div>
        </div>
    </div>
</div>
@endsection
