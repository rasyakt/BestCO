@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <x-page-header
        eyebrow="System Settings"
        title="Kustomisasi Tema"
        subtitle="Pilih palet warna enterprise untuk seluruh antarmuka. Perubahan diterapkan langsung melalui token CSS Tailwind v4 yang tersimpan di basis data.">
        <x-slot:actions>
            <form method="POST" action="{{ route('theme.reset') }}">
                @csrf
                <button type="submit" onclick="return confirm('Kembalikan tema ke Enterprise Blue default?')"
                    class="btn btn-secondary">
                    <x-icon name="refresh" class="h-4 w-4" />
                    Reset ke Default
                </button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <!-- Live preview -->
    <div class="card overflow-hidden">
        <div class="card-header">
            <div>
                <h2 class="card-title">Pratinjau Komponen</h2>
                <p class="card-subtitle">Tampilan komponen utama dengan tema yang sedang aktif</p>
            </div>
            <span class="badge badge-brand">Live Preview</span>
        </div>

        <div class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-5">
            <div class="space-y-3 lg:col-span-3">
                <div class="flex flex-wrap items-center gap-2.5">
                    <button type="button" class="btn btn-primary">
                        <x-icon name="plus" class="h-4 w-4" />
                        Tombol Primer
                    </button>
                    <button type="button" class="btn btn-secondary">Tombol Sekunder</button>
                    <span class="badge badge-brand">Badge Aktif</span>
                    <span class="badge badge-success">Selesai</span>
                    <span class="badge badge-danger">VOID</span>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                        <p class="stat-label">Omzet Hari Ini</p>
                        <p class="mt-2 text-xl font-extrabold text-slate-900 num-tabular">Rp 1.250.000</p>
                        <span class="mt-2 inline-flex items-center gap-1 text-[10px] font-bold text-emerald-600">
                            <x-icon name="trend-up" class="h-3 w-3" />
                            +12,5% dari kemarin
                        </span>
                    </div>
                    <div class="rounded-xl border border-slate-100 bg-slate-50/60 p-4">
                        <p class="stat-label">Saldo Kas</p>
                        <p class="mt-2 text-xl font-extrabold text-brand-700 num-tabular">Rp 3.480.000</p>
                        <div class="mt-2.5 progress-track">
                            <div class="progress-bar bg-brand-500" style="width: 72%"></div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="chip">INV-2026-0001</span>
                    <span class="chip">CASH</span>
                    <span class="chip">QRIS</span>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="relative h-full overflow-hidden rounded-2xl p-5 text-white"
                    style="background:
                        radial-gradient(90% 70% at 10% 0%, color-mix(in srgb, var(--color-primary-500) 35%, transparent) 0%, transparent 65%),
                        linear-gradient(160deg, var(--color-primary-950) 0%, #080f1f 100%);">
                    <div class="pointer-events-none absolute -right-8 -top-10 h-28 w-28 rounded-full border border-white/10"></div>
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-white text-[10px] font-extrabold text-brand-900">BC</span>
                        <div>
                            <p class="text-[11px] font-extrabold tracking-[0.08em]">BESTCO</p>
                            <p class="text-[8px] font-bold uppercase tracking-[0.18em] text-white/40">Teaching Factory</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-1.5">
                        <div class="flex items-center gap-2 rounded-lg bg-white/10 px-2.5 py-2 text-[10px] font-bold">
                            <x-icon name="dashboard" class="h-3.5 w-3.5 text-brand-300" />
                            Dashboard
                        </div>
                        <div class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-[10px] font-semibold text-white/50">
                            <x-icon name="pos" class="h-3.5 w-3.5" />
                            Kasir POS
                        </div>
                        <div class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-[10px] font-semibold text-white/50">
                            <x-icon name="package" class="h-3.5 w-3.5" />
                            Persediaan
                        </div>
                    </div>

                    <div class="mt-5 rounded-xl border border-white/10 bg-white/[0.06] p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[8px] font-extrabold uppercase tracking-[0.14em] text-white/35">Status Shift</span>
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        </div>
                        <p class="mt-1.5 font-mono text-[11px] font-bold">SHIFT-A-2026</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Presets -->
    <div>
        <div class="mb-4 flex items-end justify-between gap-3">
            <div>
                <h2 class="text-base font-extrabold tracking-tight text-slate-900">Palet Tersedia</h2>
                <p class="mt-0.5 text-xs text-slate-500">Empat preset enterprise yang telah dikurasi untuk konsistensi visual.</p>
            </div>
            <span class="badge badge-neutral shrink-0 whitespace-nowrap">{{ count($presets) }} preset</span>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($presets as $key => $preset)
                @php
                    $isActive = collect($preset['palette'])->every(fn ($value, $token) => ($currentPalette[$token] ?? null) === $value);
                @endphp
                <div class="card p-5 {{ $isActive ? 'ring-2 ring-brand-500 ring-offset-2' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 items-center justify-center rounded-xl text-white shadow-sm"
                                style="background-color: {{ $preset['palette']['--color-primary-700'] }};">
                                <x-icon name="palette" class="h-5 w-5" />
                            </span>
                            <div>
                                <h3 class="text-sm font-extrabold text-slate-900">{{ $preset['name'] }}</h3>
                                <p class="mt-0.5 text-[10px] font-semibold text-slate-400">{{ $isActive ? 'Sedang digunakan' : 'Siap diterapkan' }}</p>
                            </div>
                        </div>
                        @if($isActive)
                            <span class="badge badge-success"><x-icon name="check" class="h-3 w-3" /> Aktif</span>
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-6 gap-1.5">
                        @foreach(['--color-primary-50', '--color-primary-200', '--color-primary-400', '--color-primary-600', '--color-primary-800', '--color-primary-950'] as $shade)
                            <div class="group relative h-11 overflow-hidden rounded-lg border border-slate-200/60"
                                style="background-color: {{ $preset['palette'][$shade] }};"
                                title="{{ str_replace('--color-primary-', '', $shade) }}">
                                <span class="absolute bottom-1 left-1.5 text-[8px] font-extrabold {{ in_array($shade, ['--color-primary-600', '--color-primary-800', '--color-primary-950'], true) ? 'text-white/70' : 'text-slate-500/70' }}">
                                    {{ str_replace('--color-primary-', '', $shade) }}
                                </span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-4 flex items-center justify-between border-t border-slate-100 pt-4">
                        <span class="font-mono text-[10px] text-slate-400">{{ $key }}</span>
                        <form method="POST" action="{{ route('theme.preset') }}">
                            @csrf
                            <input type="hidden" name="preset_key" value="{{ $key }}">
                            <button type="submit" class="btn btn-sm {{ $isActive ? 'btn-secondary' : 'btn-primary' }}" {{ $isActive ? 'disabled' : '' }}>
                                {{ $isActive ? 'Tema Aktif' : 'Terapkan Tema' }}
                                @if(! $isActive)
                                    <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                                @endif
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="alert alert-info">
        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
        <span class="leading-relaxed">Tema memengaruhi seluruh token <span class="font-mono text-[10px]">--color-primary-*</span>, termasuk sidebar, tombol, badge, grafik, dan fokus input. Perubahan tidak memerlukan rebuild aset.</span>
    </div>
</div>
@endsection
