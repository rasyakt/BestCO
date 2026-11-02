@extends('layouts.app', ['title' => 'Pengaturan QRIS — BESTCO'])

@section('content')
<div class="container mx-auto max-w-4xl px-4 py-8">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex items-center gap-2 text-xs text-slate-500">
            <a href="{{ route('dashboard') }}" class="hover:text-brand-600">Dashboard</a>
            <x-icon name="chevron-right" class="h-3 w-3" />
            <span class="font-medium text-slate-700">Pengaturan QRIS</span>
        </div>
        <h1 class="mt-2 text-2xl font-bold text-slate-900">Pengaturan QR Code QRIS</h1>
        <p class="mt-1 text-sm text-slate-500">Upload dan kelola QR code QRIS untuk pembayaran non-tunai</p>
    </div>

    @if(session('success'))
        <div class="alert alert-success mb-6">
            <x-icon name="check-circle" class="h-4 w-4 shrink-0" />
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger mb-6">
            <x-icon name="x-circle" class="h-4 w-4 shrink-0" />
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2">
        <!-- Upload Form -->
        <div class="card">
            <div class="card-header">
                <h2 class="text-base font-semibold text-slate-900">Upload QR Code</h2>
                <p class="mt-1 text-xs text-slate-500">File PNG, JPG, atau JPEG (Maks. 2MB)</p>
            </div>
            
            <form action="{{ route('settings.qris.upload') }}" method="POST" enctype="multipart/form-data" class="card-body">
                @csrf
                
                <div>
                    <label class="form-label">Pilih file QR code QRIS</label>
                    <input type="file" name="qris_image" accept="image/png,image/jpeg,image/jpg" 
                        class="block w-full text-sm text-slate-500
                            file:mr-4 file:py-2 file:px-4
                            file:rounded-lg file:border-0
                            file:text-sm file:font-semibold
                            file:bg-brand-50 file:text-brand-700
                            hover:file:bg-brand-100
                            cursor-pointer"
                        required>
                    @error('qris_image')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="alert alert-info mt-4">
                    <x-icon name="info" class="h-4 w-4 shrink-0" />
                    <div class="text-xs leading-relaxed">
                        <p class="font-semibold">Tips:</p>
                        <ul class="mt-1 list-inside list-disc space-y-0.5">
                            <li>Gunakan QR code QRIS dari bank atau payment gateway Anda</li>
                            <li>Pastikan QR code jelas dan mudah di-scan</li>
                            <li>Ukuran file tidak lebih dari 2MB</li>
                        </ul>
                    </div>
                </div>

                <div class="mt-4 flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <x-icon name="upload" class="h-4 w-4" />
                        Upload QR Code
                    </button>
                    <a href="{{ route('dashboard') }}" class="btn btn-secondary">
                        Kembali
                    </a>
                </div>
            </form>
        </div>

        <!-- Preview -->
        <div class="card">
            <div class="card-header">
                <h2 class="text-base font-semibold text-slate-900">Preview QR Code</h2>
                <p class="mt-1 text-xs text-slate-500">QR code yang sedang aktif</p>
            </div>
            
            <div class="card-body">
                @if($qrisImagePath && Storage::disk('public')->exists($qrisImagePath))
                    <div class="space-y-4">
                        <div class="flex items-center justify-center rounded-lg border-2 border-dashed border-slate-200 bg-slate-50 p-6">
                            <img src="{{ Storage::url($qrisImagePath) }}" alt="QRIS Code" class="h-64 w-64 rounded-lg bg-white object-contain shadow-sm">
                        </div>
                        
                        <div class="rounded-lg bg-emerald-50 border border-emerald-200 p-3 text-center">
                            <p class="text-xs font-semibold text-emerald-800">✓ QR Code aktif dan siap digunakan</p>
                        </div>

                        <form action="{{ route('settings.qris.delete') }}" method="POST" 
                            onsubmit="return confirm('Yakin ingin menghapus QR code QRIS?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger w-full">
                                <x-icon name="trash" class="h-4 w-4" />
                                Hapus QR Code
                            </button>
                        </form>
                    </div>
                @else
                    <div class="empty-state py-12">
                        <span class="empty-state-icon">
                            <x-icon name="qr" class="h-6 w-6" />
                        </span>
                        <p class="text-sm font-semibold text-slate-700">Belum ada QR code</p>
                        <p class="mt-1 text-xs leading-relaxed text-slate-400">Upload QR code QRIS di form sebelah kiri</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Info Card -->
    <div class="card mt-6">
        <div class="card-body">
            <div class="flex gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-slate-50 text-slate-500">
                    <x-icon name="info" class="h-5 w-5" />
                </div>
                <div class="flex-1 text-sm text-slate-600">
                    <p class="font-semibold text-slate-900">Tentang QRIS</p>
                    <p class="mt-1 leading-relaxed">QR Code Indonesian Standard (QRIS) adalah standar pembayaran menggunakan QR Code dari Bank Indonesia. Dengan mengupload QR code QRIS, pelanggan bisa membayar melalui berbagai aplikasi e-wallet dan mobile banking.</p>
                    <p class="mt-2 leading-relaxed">Setelah upload, QR code akan otomatis muncul di terminal POS kasir ketika memilih metode pembayaran QRIS.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
