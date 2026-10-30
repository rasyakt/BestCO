@extends('layouts.app')

@section('content')
<div class="space-y-6 max-w-4xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900 tracking-tight">Kustomisasi Tema Dinamis (Superadmin)</h1>
            <p class="text-xs text-slate-500 mt-0.5">Pilih palet warna Enterprise berbasis token CSS Tailwind CSS v4 yang tersimpan di basis data.</p>
        </div>

        <form method="POST" action="{{ route('theme.reset') }}">
            @csrf
            <button type="submit" onclick="return confirm('Kembalikan tema ke Enterprise Blue default?')"
                class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded text-xs font-semibold border border-slate-300">
                Reset ke Default
            </button>
        </form>
    </div>

    <!-- Live Preview Card -->
    <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-xs space-y-3">
        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 border-b pb-2">Pratinjau Komponen dengan Tema Saat Ini</h2>
        <div class="flex flex-wrap items-center gap-3 text-xs">
            <button class="px-3.5 py-2 bg-brand-700 text-white font-semibold rounded shadow-xs">
                Tombol Primer Brand
            </button>
            <button class="px-3.5 py-2 bg-brand-50 text-brand-700 font-semibold rounded border border-brand-200">
                Tombol Sekunder Brand
            </button>
            <span class="inline-flex px-2.5 py-1 rounded text-xs font-semibold bg-brand-50 text-brand-700 border border-brand-200">
                Badge Aktif
            </span>
            <span class="font-bold text-brand-700 num-tabular text-base">
                Rp 1.250.000,0000
            </span>
        </div>
    </div>

    <!-- Presets Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($presets as $key => $preset)
            <div class="bg-white p-5 rounded-lg border border-slate-200 shadow-xs space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="font-bold text-sm text-slate-900">{{ $preset['name'] }}</h3>
                    <form method="POST" action="{{ route('theme.preset') }}">
                        @csrf
                        <input type="hidden" name="preset_key" value="{{ $key }}">
                        <button type="submit" class="px-3 py-1 bg-brand-700 hover:bg-brand-800 text-white rounded text-xs font-semibold shadow-xs">
                            Terapkan Tema
                        </button>
                    </form>
                </div>

                <!-- Palette Color Chips -->
                <div class="grid grid-cols-6 gap-1.5">
                    @foreach(['--color-primary-50', '--color-primary-200', '--color-primary-400', '--color-primary-600', '--color-primary-800', '--color-primary-950'] as $shade)
                        <div class="h-10 rounded border border-slate-200 flex flex-col justify-end p-1 text-[9px] font-mono"
                            style="background-color: {{ $preset['palette'][$shade] }}; color: {{ in_array($shade, ['--color-primary-600', '--color-primary-800', '--color-primary-950']) ? '#ffffff' : '#0f172a' }};">
                            {{ str_replace('--color-primary-', '', $shade) }}
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
