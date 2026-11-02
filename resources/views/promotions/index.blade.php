@extends('layouts.app')

@section('content')
<div class="space-y-6" x-data="{
    confirmToggle(form, name, isCurrentlyActive) {
        window.bestcoDialog.confirm({
            title: isCurrentlyActive ? 'Nonaktifkan Promo?' : 'Aktifkan Promo?',
            message: isCurrentlyActive 
                ? 'Promo ' + name + ' tidak akan otomatis terpasang di kasir hingga diaktifkan kembali.'
                : 'Promo ' + name + ' akan langsung berlaku di kasir jika sudah memasuki rentang waktu aktif.',
            type: isCurrentlyActive ? 'warning' : 'success',
            confirmText: isCurrentlyActive ? 'Nonaktifkan' : 'Aktifkan',
            cancelText: 'Batal'
        }).then(confirmed => {
            if (confirmed) form.submit();
        });
    },
    confirmDelete(form, name) {
        window.bestcoDialog.confirm({
            title: 'Hapus Promo?',
            message: 'Promo ' + name + ' akan dihapus permanen. Transaksi masa lalu yang sudah menggunakan promo ini tidak akan terpengaruh.',
            type: 'danger',
            confirmText: 'Hapus Promo',
            cancelText: 'Batal'
        }).then(confirmed => {
            if (confirmed) form.submit();
        });
    }
}">
    <!-- Header Halaman -->
    <x-page-header
        title="Diskon & Promo"
        subtitle="Kelola program promosi berjadwal, diskon persentase/rupiah, dan kupon kasir otomatis.">
        <x-slot:actions>
            <a href="{{ route('promotions.create') }}" class="btn btn-primary">
                <x-icon name="plus" class="h-4 w-4" />
                <span>Buat Promo Baru</span>
            </a>
        </x-slot:actions>
    </x-page-header>

    <!-- Ringkasan Statistik -->
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Total Program Promo</p>
                    <p class="stat-value num-tabular">{{ $counts['all'] }}</p>
                </div>
                <span class="stat-icon text-brand-700">
                    <x-icon name="tag" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                Seluruh program promo tercatat
            </p>
        </div>

        <div class="card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Sedang Aktif Saat Ini</p>
                    <p class="stat-value text-emerald-600 num-tabular">{{ $counts['active'] }}</p>
                </div>
                <span class="stat-icon text-emerald-600">
                    <x-icon name="check-circle" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                Berlaku otomatis di kasir
            </p>
        </div>

        <div class="card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Terjadwal Mendatang</p>
                    <p class="stat-value text-sky-600 num-tabular">{{ $counts['scheduled'] }}</p>
                </div>
                <span class="stat-icon text-sky-600">
                    <x-icon name="clock" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                Menunggu rentang waktu mulai
            </p>
        </div>

        <div class="card p-5">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-label">Selesai / Nonaktif</p>
                    <p class="stat-value text-slate-500 num-tabular">{{ $counts['expired'] + $counts['inactive'] }}</p>
                </div>
                <span class="stat-icon text-slate-400">
                    <x-icon name="x-circle" class="h-4 w-4" />
                </span>
            </div>
            <p class="mt-3 border-t border-slate-100 pt-3 text-[11px] text-slate-500">
                {{ $counts['expired'] }} kedaluwarsa &bull; {{ $counts['inactive'] }} dinonaktifkan
            </p>
        </div>
    </div>

    <!-- Filter Tab & Pencarian -->
    <div class="card p-4">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <!-- Navigasi Tab -->
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('promotions.index', ['tab' => 'all', 'q' => $search]) }}"
                    class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $tab === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    Semua <span class="ml-1 opacity-75">({{ $counts['all'] }})</span>
                </a>
                <a href="{{ route('promotions.index', ['tab' => 'active', 'q' => $search]) }}"
                    class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $tab === 'active' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    Sedang Aktif <span class="ml-1 opacity-75">({{ $counts['active'] }})</span>
                </a>
                <a href="{{ route('promotions.index', ['tab' => 'scheduled', 'q' => $search]) }}"
                    class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $tab === 'scheduled' ? 'bg-sky-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    Terjadwal <span class="ml-1 opacity-75">({{ $counts['scheduled'] }})</span>
                </a>
                <a href="{{ route('promotions.index', ['tab' => 'expired', 'q' => $search]) }}"
                    class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $tab === 'expired' ? 'bg-slate-700 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    Kedaluwarsa <span class="ml-1 opacity-75">({{ $counts['expired'] }})</span>
                </a>
                <a href="{{ route('promotions.index', ['tab' => 'inactive', 'q' => $search]) }}"
                    class="rounded-md px-3 py-1.5 text-xs font-semibold transition {{ $tab === 'inactive' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    Dinonaktifkan <span class="ml-1 opacity-75">({{ $counts['inactive'] }})</span>
                </a>
            </div>

            <!-- Form Pencarian -->
            <form method="GET" action="{{ route('promotions.index') }}" class="flex items-center gap-2">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <div class="relative w-full sm:w-64">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <x-icon name="search" class="h-3.5 w-3.5" />
                    </span>
                    <input type="text" name="q" value="{{ $search }}" placeholder="Cari nama atau kode promo..."
                        class="input w-full py-1.5 pl-8 pr-3 text-xs">
                </div>
                <button type="submit" class="btn btn-dark btn-sm">Cari</button>
                @if($search !== '')
                    <a href="{{ route('promotions.index', ['tab' => $tab]) }}" class="btn btn-ghost btn-sm">Reset</a>
                @endif
            </form>
        </div>
    </div>

    <!-- Tabel Data Promo (Menggunakan Komponen Standar BESTCO) -->
    <div class="table-shell">
        <div class="card-header">
            <div>
                <h2 class="card-title">Daftar Program Promo</h2>
                <p class="card-subtitle">
                    @if($promotions->total() > 0)
                        Menampilkan {{ $promotions->firstItem() }}–{{ $promotions->lastItem() }} dari total {{ $promotions->total() }} promo tercatat.
                    @else
                        Tidak ada program promo yang sesuai dengan filter
                    @endif
                </p>
            </div>
            <span class="badge badge-neutral">{{ $promotions->total() }} Promo</span>
        </div>

        @if($promotions->isEmpty())
            <div class="empty-state py-12">
                <span class="empty-state-icon">
                    <x-icon name="tag" class="h-5 w-5" />
                </span>
                <p class="text-sm font-semibold text-slate-700">Belum ada promo pada filter ini</p>
                <p class="mt-1 max-w-sm text-xs leading-relaxed text-slate-400">
                    Tidak ada program promosi yang sedang berada pada status ini. Anda dapat beralih ke tab lain atau membuat promo baru.
                </p>
                <a href="{{ route('promotions.create') }}" class="btn btn-primary btn-sm mt-4">
                    <x-icon name="plus" class="h-3.5 w-3.5" />
                    Buat Promo Baru
                </a>
            </div>
        @else
            <div class="w-full overflow-x-auto">
                <table class="data-table w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="w-12 text-left whitespace-nowrap px-4 py-3">No</th>
                            <th class="text-left whitespace-nowrap px-4 py-3">Program Promo</th>
                            <th class="text-left whitespace-nowrap px-4 py-3">Cakupan Target</th>
                            <th class="text-left whitespace-nowrap px-4 py-3">Nilai Potongan</th>
                            <th class="text-left whitespace-nowrap px-4 py-3">Periode Berlaku</th>
                            <th class="text-center whitespace-nowrap px-4 py-3">Status</th>
                            <th class="text-right whitespace-nowrap px-4 py-3 w-[110px]">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($promotions as $index => $promo)
                            @php
                                $isNowActive = $promo->isCurrentlyActive();
                                $isScheduled = $promo->isScheduled();
                                $isExpired = $promo->isExpired();
                            @endphp
                            <tr>
                                <td class="text-slate-400 num-tabular px-4 py-3">{{ $promotions->firstItem() + $index }}</td>
                                
                                <!-- Program Promo & Kode -->
                                <td class="px-4 py-3 max-w-xs">
                                    <div class="flex items-start gap-2.5">
                                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md border {{ $isNowActive ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : ($isScheduled ? 'border-sky-200 bg-sky-50 text-sky-700' : 'border-slate-200 bg-slate-50 text-slate-500') }}">
                                            <x-icon name="tag" class="h-4 w-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <p class="font-semibold text-slate-900 leading-tight">{{ $promo->name }}</p>
                                                @if($promo->code)
                                                    <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[10px] font-bold text-slate-700 border border-slate-200">
                                                        {{ $promo->code }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if($promo->description)
                                                <p class="mt-0.5 text-[11px] text-slate-500 line-clamp-1">{{ $promo->description }}</p>
                                            @endif
                                            <p class="mt-1 text-[10px] text-slate-400">Oleh: {{ $promo->creator->name }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Cakupan Target -->
                                <td class="px-4 py-3">
                                    <span class="badge badge-neutral text-[10px]">
                                        {{ $promo->scope->label() }}
                                    </span>
                                    <p class="mt-1 font-medium text-slate-800 text-[11px]">
                                        @if($promo->scope === \App\Enums\PromotionScope::PRODUCT)
                                            {{ $promo->product?->name ?? 'Produk Dihapus' }}
                                        @elseif($promo->scope === \App\Enums\PromotionScope::SERVICE)
                                            {{ $promo->service?->name ?? 'Layanan Dihapus' }}
                                        @elseif($promo->scope === \App\Enums\PromotionScope::CATEGORY)
                                            Kategori: {{ $promo->category?->name ?? 'Kategori Dihapus' }}
                                        @elseif($promo->scope === \App\Enums\PromotionScope::MIN_SPEND)
                                            Min. Belanja: Rp {{ number_format((float) $promo->min_order_amount, 0, ',', '.') }}
                                        @else
                                            Semua Produk & Jasa
                                        @endif
                                    </p>
                                    @if($promo->min_order_amount > 0 && $promo->scope !== \App\Enums\PromotionScope::MIN_SPEND)
                                        <p class="text-[10px] text-slate-400">Syarat min. Rp {{ number_format((float) $promo->min_order_amount, 0, ',', '.') }}</p>
                                    @endif
                                </td>

                                <!-- Nilai Potongan -->
                                <td class="px-4 py-3">
                                    @if($promo->discount_type === \App\Enums\PromotionDiscountType::PERCENTAGE)
                                        <p class="font-bold text-brand-700 text-sm num-tabular">
                                            {{ rtrim(rtrim((string) $promo->discount_value, '0'), '.') }}%
                                        </p>
                                        @if($promo->max_discount_amount)
                                            <p class="text-[10px] text-slate-400">Maks. Rp {{ number_format((float) $promo->max_discount_amount, 0, ',', '.') }}</p>
                                        @endif
                                    @else
                                        <p class="font-bold text-brand-700 text-sm num-tabular">
                                            Rp {{ number_format((float) $promo->discount_value, 0, ',', '.') }}
                                        </p>
                                        <p class="text-[10px] text-slate-400">Potongan langsung</p>
                                    @endif
                                </td>

                                <!-- Periode Berlaku -->
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="space-y-0.5 text-[11px]">
                                        <p class="text-slate-800 font-medium">{{ $promo->start_datetime->format('d M Y, H:i') }}</p>
                                        <p class="text-slate-500 flex items-center gap-1">
                                            <span class="text-slate-400">s.d.</span>
                                            <span>{{ $promo->end_datetime->format('d M Y, H:i') }}</span>
                                        </p>
                                    </div>
                                </td>

                                <!-- Status -->
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    @if(! $promo->is_active)
                                        <span class="badge badge-danger">Nonaktif</span>
                                    @elseif($isNowActive)
                                        <span class="badge badge-success">Aktif Berjalan</span>
                                    @elseif($isScheduled)
                                        <span class="badge badge-info">Terjadwal</span>
                                    @else
                                        <span class="badge badge-neutral">Kedaluwarsa</span>
                                    @endif
                                </td>

                                <!-- Aksi -->
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-1">
                                        <!-- Toggle Status Cepat -->
                                        <form method="POST" action="{{ route('promotions.toggle', $promo) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="button" @click="confirmToggle($el.closest('form'), '{{ addslashes($promo->name) }}', {{ $promo->is_active ? 'true' : 'false' }})"
                                                class="rounded p-1 transition {{ $promo->is_active ? 'text-emerald-600 hover:bg-emerald-50' : 'text-slate-400 hover:bg-slate-100 hover:text-slate-600' }}"
                                                title="{{ $promo->is_active ? 'Klik untuk menonaktifkan' : 'Klik untuk mengaktifkan' }}">
                                                <x-icon name="{{ $promo->is_active ? 'check-circle' : 'x-circle' }}" class="h-4 w-4" />
                                            </button>
                                        </form>

                                        <!-- Edit -->
                                        <a href="{{ route('promotions.edit', $promo) }}"
                                            class="rounded p-1 text-slate-500 transition hover:bg-slate-100 hover:text-slate-900"
                                            title="Ubah promo">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>

                                        <!-- Hapus (Admin & Superadmin) -->
                                        @if(auth()->user()?->hasAnyRole(['superadmin', 'admin']))
                                            <form method="POST" action="{{ route('promotions.destroy', $promo) }}" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" @click="confirmDelete($el.closest('form'), '{{ addslashes($promo->name) }}')"
                                                    class="rounded p-1 text-slate-400 transition hover:bg-rose-50 hover:text-rose-600"
                                                    title="Hapus promo">
                                                    <x-icon name="trash" class="h-4 w-4" />
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($promotions->hasPages())
                <div class="border-t border-slate-100 p-4">
                    {{ $promotions->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
