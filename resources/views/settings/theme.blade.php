@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    <x-page-header
        title="Tema"
        subtitle="Atur warna tampilan aplikasi.">
        <x-slot:actions>
            <form method="POST" action="{{ route('theme.reset') }}">
                @csrf
                <button type="submit" onclick="return confirm('Kembalikan ke tema biru default?')"
                    class="btn btn-secondary">
                    <x-icon name="refresh" class="h-4 w-4" />
                    Kembalikan default
                </button>
            </form>
        </x-slot:actions>
    </x-page-header>

    <!-- Pratinjau -->
    <div class="card overflow-hidden">
        <div class="card-header">
            <div>
                <h2 class="card-title">Pratinjau</h2>
                <p class="card-subtitle">Contoh tampilan dengan tema aktif</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 p-5 lg:grid-cols-5">
            <div class="space-y-3 lg:col-span-3">
                <div class="flex flex-wrap items-center gap-2.5">
                    <button type="button" class="btn btn-primary">
                        <x-icon name="plus" class="h-4 w-4" />
                        Tombol utama
                    </button>
                    <button type="button" class="btn btn-secondary">Tombol kedua</button>
                    <span class="badge badge-brand">Aktif</span>
                    <span class="badge badge-success">Selesai</span>
                    <span class="badge badge-danger">Dibatalkan</span>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                        <p class="stat-label">Penjualan hari ini</p>
                        <p class="mt-1.5 text-xl font-semibold text-slate-900 num-tabular">Rp 1.250.000</p>
                        <span class="mt-1.5 inline-flex items-center gap-1 text-[10px] font-medium text-emerald-600">
                            <x-icon name="trend-up" class="h-3 w-3" />
                            Naik 12,5% dari kemarin
                        </span>
                    </div>
                    <div class="rounded-md border border-slate-200 bg-slate-50 p-4">
                        <p class="stat-label">Saldo kas</p>
                        <p class="mt-1.5 text-xl font-semibold text-brand-700 num-tabular">Rp 3.480.000</p>
                        <div class="mt-2.5 progress-track">
                            <div class="progress-bar bg-brand-500" style="width: 72%"></div>
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="chip">INV-2026-0001</span>
                    <span class="chip">Tunai</span>
                    <span class="chip">QRIS</span>
                </div>

                <div class="overflow-hidden rounded-md border border-slate-200">
                    <table class="w-full text-left text-[10px]">
                        <thead class="bg-slate-50 text-slate-500">
                            <tr>
                                <th class="px-3 py-2 font-medium">Barang</th>
                                <th class="px-3 py-2 text-right font-medium">Total</th>
                                <th class="px-3 py-2 text-right font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr>
                                <td class="px-3 py-2 text-slate-700">Print Hitam Putih</td>
                                <td class="px-3 py-2 text-right text-slate-800 num-tabular">Rp 500</td>
                                <td class="px-3 py-2 text-right"><span class="badge badge-success">Selesai</span></td>
                            </tr>
                            <tr>
                                <td class="px-3 py-2 text-slate-700">Kertas HVS A4</td>
                                <td class="px-3 py-2 text-right text-slate-800 num-tabular">Rp 55.000</td>
                                <td class="px-3 py-2 text-right"><span class="badge badge-danger">Dibatalkan</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="h-full rounded-md bg-slate-900 p-5 text-white">
                    <div class="flex items-center gap-2.5">
                        <span class="flex h-9 w-9 items-center justify-center rounded-md bg-white text-[10px] font-bold text-slate-900">BC</span>
                        <div>
                            <p class="text-[11px] font-semibold">BESTCO</p>
                            <p class="text-[10px] text-slate-400">Teaching Factory MPLB</p>
                        </div>
                    </div>

                    <div class="mt-5 space-y-1">
                        <div class="flex items-center gap-2 rounded bg-white/10 px-2.5 py-2 text-[10px] font-medium">
                            <x-icon name="dashboard" class="h-3.5 w-3.5 text-slate-300" />
                            Dashboard
                        </div>
                        <div class="flex items-center gap-2 rounded px-2.5 py-2 text-[10px] text-slate-400">
                            <x-icon name="pos" class="h-3.5 w-3.5" />
                            Kasir
                        </div>
                        <div class="flex items-center gap-2 rounded px-2.5 py-2 text-[10px] text-slate-400">
                            <x-icon name="package" class="h-3.5 w-3.5" />
                            Persediaan
                        </div>
                    </div>

                    <div class="mt-5 rounded border border-white/10 bg-white/5 p-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] text-slate-400">Shift kasir</span>
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                        </div>
                        <p class="mt-1.5 font-mono text-[11px] font-medium">SHIFT-A-2026</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pilihan tema -->
    <div>
        <div class="mb-4 flex items-end justify-between gap-3">
            <div>
                <h2 class="text-base font-semibold text-slate-900">Pilihan tema</h2>
                <p class="mt-0.5 text-xs text-slate-500">Empat warna siap pakai.</p>
            </div>
            <span class="text-[11px] text-slate-500">{{ count($presets) }} tema</span>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            @foreach($presets as $key => $preset)
                @php
                    $isActive = collect($preset['palette'])->every(fn ($value, $token) => ($currentPalette[$token] ?? null) === $value);
                @endphp
                <div class="card p-5 {{ $isActive ? 'border-brand-500 ring-1 ring-brand-500' : '' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <span class="h-9 w-9 rounded-md" style="background-color: {{ $preset['palette']['--color-primary-700'] }};"></span>
                            <div>
                                <h3 class="text-sm font-semibold text-slate-900">{{ $preset['name'] }}</h3>
                                <p class="mt-0.5 text-[10px] text-slate-400">{{ $isActive ? 'Sedang dipakai' : 'Siap dipakai' }}</p>
                            </div>
                        </div>
                        @if($isActive)
                            <span class="badge badge-success">Aktif</span>
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-6 gap-1.5">
                        @foreach(['--color-primary-50', '--color-primary-200', '--color-primary-400', '--color-primary-600', '--color-primary-800', '--color-primary-950'] as $shade)
                            <div class="group relative h-11 overflow-hidden rounded border border-slate-200"
                                style="background-color: {{ $preset['palette'][$shade] }};"
                                title="{{ str_replace('--color-primary-', '', $shade) }}">
                                <span class="absolute bottom-1 left-1.5 text-[8px] font-medium {{ in_array($shade, ['--color-primary-600', '--color-primary-800', '--color-primary-950'], true) ? 'text-white/70' : 'text-slate-500/70' }}">
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
                                {{ $isActive ? 'Sedang dipakai' : 'Pakai tema' }}
                            </button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="alert alert-info">
        <x-icon name="info" class="mt-0.5 h-4 w-4 shrink-0" />
        <span class="leading-relaxed">Warna berlaku di seluruh aplikasi, termasuk tombol, menu, dan tabel. Tidak perlu build ulang.</span>
    </div>
</div>
@endsection
