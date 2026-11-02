/**
 * Date picker kalender BESTCO.
 *
 * Komponen Alpine ini sengaja tidak memakai date picker bawaan browser.
 * Tanggal internal selalu berformat Y-m-d (aman untuk input hidden/backend),
 * sedangkan label ditampilkan dalam format Indonesia.
 */

const MONTHS = [
    'Januari',
    'Februari',
    'Maret',
    'April',
    'Mei',
    'Juni',
    'Juli',
    'Agustus',
    'September',
    'Oktober',
    'November',
    'Desember',
];

const WEEKDAYS = ['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'];

const POPUP_WIDTH = 304;
const VIEWPORT_MARGIN = 12;
const POPUP_GAP = 8;

function pad(value) {
    return String(value).padStart(2, '0');
}

function toKey(year, monthIndex, day) {
    return `${year}-${pad(monthIndex + 1)}-${pad(day)}`;
}

function parseKey(key) {
    const [year, month, day] = String(key).split('-').map(Number);

    return new Date(year, (month || 1) - 1, day || 1);
}

function todayKey() {
    const today = new Date();

    return toKey(today.getFullYear(), today.getMonth(), today.getDate());
}

export function bestcoDatePicker({ value = null, min = null, max = null } = {}) {
    return {
        open: false,
        selected: value || '',
        min: min || '',
        max: max || '',
        viewYear: 0,
        viewMonth: 0,
        popupStyle: '',

        init() {
            const base = this.selected ? parseKey(this.selected) : new Date();

            this.viewYear = base.getFullYear();
            this.viewMonth = base.getMonth();
        },

        get displayValue() {
            if (!this.selected) {
                return '';
            }

            return new Intl.DateTimeFormat('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            }).format(parseKey(this.selected));
        },

        get monthLabel() {
            return `${MONTHS[this.viewMonth]} ${this.viewYear}`;
        },

        get weekdays() {
            return WEEKDAYS;
        },

        get canSelectToday() {
            return !this.isDisabled(todayKey());
        },

        get days() {
            const firstOfMonth = new Date(this.viewYear, this.viewMonth, 1);
            // Senin = 0 ... Minggu = 6
            const leading = (firstOfMonth.getDay() + 6) % 7;
            const daysInMonth = new Date(this.viewYear, this.viewMonth + 1, 0).getDate();
            const cellCount = Math.ceil((leading + daysInMonth) / 7) * 7;
            const today = todayKey();
            const formatter = new Intl.DateTimeFormat('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric',
            });

            return Array.from({ length: cellCount }, (_, index) => {
                const date = new Date(this.viewYear, this.viewMonth, 1 - leading + index);
                const year = date.getFullYear();
                const month = date.getMonth();
                const day = date.getDate();
                const key = toKey(year, month, day);

                return {
                    key,
                    day,
                    outside: month !== this.viewMonth,
                    today: key === today,
                    selected: key === this.selected,
                    disabled: this.isDisabled(key),
                    label: formatter.format(date),
                };
            });
        },

        isDisabled(key) {
            return Boolean((this.min && key < this.min) || (this.max && key > this.max));
        },

        toggle() {
            if (this.open) {
                this.close();
                return;
            }

            this.show();
        },

        show() {
            const base = this.selected ? parseKey(this.selected) : new Date();

            this.viewYear = base.getFullYear();
            this.viewMonth = base.getMonth();
            this.open = true;

            this.$nextTick(() => {
                this.positionPopup();
                this.$refs.popup?.focus({ preventScroll: true });
            });
        },

        close() {
            if (!this.open) {
                return;
            }

            this.open = false;
            this.$refs.trigger?.focus({ preventScroll: true });
        },

        positionPopup() {
            const trigger = this.$refs.trigger;

            if (!trigger) {
                return;
            }

            const rect = trigger.getBoundingClientRect();
            const width = Math.min(POPUP_WIDTH, window.innerWidth - VIEWPORT_MARGIN * 2);
            const height = this.$refs.popup?.offsetHeight || 340;
            const left = Math.max(
                VIEWPORT_MARGIN,
                Math.min(rect.left, window.innerWidth - width - VIEWPORT_MARGIN),
            );

            let top = rect.bottom + POPUP_GAP;

            if (top + height > window.innerHeight - VIEWPORT_MARGIN) {
                top = Math.max(VIEWPORT_MARGIN, rect.top - height - POPUP_GAP);
            }

            this.popupStyle = `top:${Math.round(top)}px;left:${Math.round(left)}px;width:${Math.round(width)}px;`;
        },

        previousMonth() {
            this.shiftMonth(-1);
        },

        nextMonth() {
            this.shiftMonth(1);
        },

        previousYear() {
            this.viewYear -= 1;
        },

        nextYear() {
            this.viewYear += 1;
        },

        shiftMonth(offset) {
            const date = new Date(this.viewYear, this.viewMonth + offset, 1);

            this.viewYear = date.getFullYear();
            this.viewMonth = date.getMonth();
        },

        pick(day) {
            if (!day || day.disabled) {
                return;
            }

            this.selected = day.key;
            this.open = false;
            this.notifyChange();
            this.$refs.trigger?.focus({ preventScroll: true });
        },

        selectToday() {
            const key = todayKey();

            if (this.isDisabled(key)) {
                return;
            }

            this.selected = key;
            this.open = false;
            this.notifyChange();
            this.$refs.trigger?.focus({ preventScroll: true });
        },

        notifyChange() {
            const input = this.$refs.input;

            if (!input) {
                return;
            }

            input.dispatchEvent(new Event('input', { bubbles: true }));
            input.dispatchEvent(new Event('change', { bubbles: true }));
        },
    };
}
