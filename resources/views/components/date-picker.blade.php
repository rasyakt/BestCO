@props([
    'name',
    'value' => null,
    'min' => null,
    'max' => null,
    'id' => null,
])

@php
    $inputValue = $value ?: now()->format('Y-m-d');
    $inputId = $id ?: $name;
@endphp

<div
    class="relative"
    x-data="bestcoDatePicker({ value: @js($inputValue), min: @js($min), max: @js($max) })"
    @click.outside="close()"
    @keydown.escape.window="close()"
    @resize.window="open && positionPopup()"
    @scroll.window="open && positionPopup()"
>
    <input type="hidden" name="{{ $name }}" id="{{ $inputId }}-value"
        x-ref="input" value="{{ $inputValue }}" :value="selected">

    <button type="button" id="{{ $inputId }}" x-ref="trigger"
        @click="toggle()"
        class="input flex cursor-pointer items-center justify-between gap-2 text-left"
        :class="open ? 'border-brand-500 ring-2 ring-brand-500/15' : ''"
        aria-haspopup="dialog"
        :aria-expanded="open.toString()"
        :aria-label="selected ? 'Ubah tanggal: ' + displayValue : 'Pilih tanggal'">
        <span class="truncate" x-text="displayValue"></span>
        <x-icon name="calendar" class="h-4 w-4 shrink-0 text-slate-400" />
    </button>

    <div x-show="open" x-cloak x-ref="popup" tabindex="-1" role="dialog" aria-label="Pilih tanggal"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="translate-y-1 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="translate-y-1 opacity-0"
        class="fixed z-50 rounded-lg border border-slate-200 bg-white p-3 shadow-xl focus:outline-none"
        :style="popupStyle">

        <div class="flex items-center justify-between pb-2">
            <div class="flex items-center gap-0.5">
                <button type="button" @click="previousYear()"
                    class="flex h-7 w-7 items-center justify-center rounded text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/30"
                    aria-label="Tahun sebelumnya">
                    <x-icon name="chevrons-left" class="h-3.5 w-3.5" />
                </button>
                <button type="button" @click="previousMonth()"
                    class="flex h-7 w-7 items-center justify-center rounded text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/30"
                    aria-label="Bulan sebelumnya">
                    <x-icon name="chevron-left" class="h-3.5 w-3.5" />
                </button>
            </div>

            <p class="text-xs font-semibold text-slate-900" x-text="monthLabel"></p>

            <div class="flex items-center gap-0.5">
                <button type="button" @click="nextMonth()"
                    class="flex h-7 w-7 items-center justify-center rounded text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/30"
                    aria-label="Bulan berikutnya">
                    <x-icon name="chevron-right" class="h-3.5 w-3.5" />
                </button>
                <button type="button" @click="nextYear()"
                    class="flex h-7 w-7 items-center justify-center rounded text-slate-400 transition-colors hover:bg-slate-100 hover:text-slate-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/30"
                    aria-label="Tahun berikutnya">
                    <x-icon name="chevrons-right" class="h-3.5 w-3.5" />
                </button>
            </div>
        </div>

        <div class="grid grid-cols-7 gap-0.5 pb-1 text-center text-[10px] font-semibold uppercase tracking-wide text-slate-400">
            <template x-for="weekday in weekdays" :key="weekday">
                <span x-text="weekday"></span>
            </template>
        </div>

        <div class="grid grid-cols-7 gap-0.5">
            <template x-for="day in days" :key="day.key">
                <button type="button" @click="pick(day)" :disabled="day.disabled"
                    :aria-label="day.label"
                    :aria-current="day.selected ? 'date' : null"
                    class="flex h-8 w-full items-center justify-center rounded text-xs font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/30"
                    :class="{
                        'bg-brand-600 text-white hover:bg-brand-700': day.selected,
                        'text-slate-700 hover:bg-slate-100': !day.selected && !day.outside && !day.disabled,
                        'text-slate-300 hover:bg-slate-100': day.outside && !day.selected && !day.disabled,
                        'cursor-not-allowed text-slate-300 opacity-50': day.disabled && !day.selected,
                        'ring-1 ring-brand-400': day.today && !day.selected,
                    }"
                    x-text="day.day"></button>
            </template>
        </div>

        <div class="mt-2 flex items-center justify-between border-t border-slate-100 pt-2">
            <button type="button" @click="selectToday()" :disabled="!canSelectToday"
                class="text-[11px] font-semibold text-brand-600 transition-colors hover:text-brand-700 disabled:pointer-events-none disabled:opacity-40">
                Hari ini
            </button>
            <button type="button" @click="close()"
                class="text-[11px] font-medium text-slate-500 transition-colors hover:text-slate-700">
                Tutup
            </button>
        </div>
    </div>
</div>
