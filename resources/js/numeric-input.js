/**
 * Penjaga input angka/nominal.
 *
 * Semua input numerik diubah menjadi type="text" + inputmode agar browser
 * tidak lagi menerima karakter aneh seperti e, E, +, -, koma, atau huruf.
 * Nilai tetap divalidasi ulang di backend (StrictCurrency/StrictQuantity).
 */
const NUMERIC_SELECTOR = 'input[type="number"], input[data-currency], input[data-quantity], input[data-numeric]';

function allowsDecimal(input) {
    if (input.dataset.allowDecimal === 'true') {
        return true;
    }

    if (input.dataset.allowDecimal === 'false') {
        return false;
    }

    // Nominal rupiah selalu bilangan bulat.
    if (input.hasAttribute('data-currency')) {
        return false;
    }

    // PIN dan angka identitas tidak memakai desimal.
    if (input.hasAttribute('data-numeric')) {
        return false;
    }

    const step = input.getAttribute('step') ?? '';

    return step.includes('.');
}

function sanitizeNumeric(value, allowDecimal, maxLength, preserveLeadingZeros) {
    let cleaned = String(value ?? '');

    cleaned = allowDecimal
        ? cleaned.replace(/[^\d.]/g, '')
        : cleaned.replace(/[^\d]/g, '');

    if (allowDecimal) {
        const parts = cleaned.split('.');
        cleaned = parts.shift() + (parts.length > 0 ? '.' + parts.join('') : '');
    }

    // Hilangkan nol di depan, tapi pertahankan "0" dan "0.x".
    // PIN dan nomor identitas tetap mempertahankan nol di depan.
    if (!preserveLeadingZeros) {
        cleaned = cleaned.replace(/^0+(?=\d)/, '');
    }

    const [integerPart, decimalPart] = cleaned.split('.');
    const maxIntegerDigits = 15;
    const maxDecimalDigits = 4;

    let result = integerPart.slice(0, maxIntegerDigits);

    if (allowDecimal && decimalPart !== undefined) {
        result += '.' + decimalPart.slice(0, maxDecimalDigits);
    }

    if (maxLength && result.length > maxLength) {
        result = result.slice(0, maxLength);
    }

    return result;
}

function guardNumericInput(input) {
    if (!(input instanceof HTMLInputElement) || input.dataset.numericGuard === '1') {
        return;
    }

    input.dataset.numericGuard = '1';

    const allowDecimal = allowsDecimal(input);
    const preserveLeadingZeros = input.dataset.allowLeadingZero === 'true';
    const existingMaxLength = Number(input.getAttribute('maxlength')) || 0;
    const maxLength = existingMaxLength || (allowDecimal ? 20 : 15);

    if (input.type === 'number') {
        input.type = 'text';
    }

    input.inputMode = allowDecimal ? 'decimal' : 'numeric';
    input.autocomplete = 'off';
    input.spellcheck = false;
    input.maxLength = maxLength;
    input.setAttribute('pattern', allowDecimal ? '[0-9]*[.]?[0-9]*' : '[0-9]*');

    const clean = (value) => sanitizeNumeric(value, allowDecimal, maxLength, preserveLeadingZeros);

    const applySanitizedValue = () => {
        const cleaned = clean(input.value);

        if (cleaned === input.value) {
            return;
        }

        const selectionStart = input.selectionStart ?? input.value.length;
        const previousLength = input.value.length;

        input.value = cleaned;

        const nextPosition = Math.max(
            0,
            selectionStart - (previousLength - cleaned.length),
        );

        try {
            input.setSelectionRange(nextPosition, nextPosition);
        } catch {
            // Beberapa browser tidak mengizinkan setSelectionRange pada input tertentu.
        }
    };

    input.addEventListener('beforeinput', (event) => {
        if (event.data && clean(event.data) !== event.data) {
            event.preventDefault();
        }
    });

    input.addEventListener('keydown', (event) => {
        if (event.ctrlKey || event.metaKey || event.altKey) {
            return;
        }

        if (event.key.length !== 1) {
            return;
        }

        const isDigit = /[0-9]/.test(event.key);
        const isDecimalPoint = allowDecimal && event.key === '.';

        if (!isDigit && !isDecimalPoint) {
            event.preventDefault();
        }
    });

    input.addEventListener('paste', (event) => {
        const clipboard = event.clipboardData || window.clipboardData;
        const pasted = clipboard?.getData('text') ?? '';
        const cleaned = clean(pasted);

        if (cleaned === pasted) {
            return;
        }

        event.preventDefault();

        const start = input.selectionStart ?? input.value.length;
        const end = input.selectionEnd ?? start;

        input.setRangeText(cleaned, start, end, 'end');
        input.dispatchEvent(new Event('input', { bubbles: true }));
    });

    input.addEventListener('input', applySanitizedValue);

    input.addEventListener('blur', () => {
        if (input.value.endsWith('.')) {
            input.value = input.value.slice(0, -1);
            input.dispatchEvent(new Event('input', { bubbles: true }));
        }
    });
}

function initNumericInputs(root = document) {
    if (root instanceof HTMLInputElement && root.matches(NUMERIC_SELECTOR)) {
        guardNumericInput(root);
    }

    root.querySelectorAll?.(NUMERIC_SELECTOR).forEach(guardNumericInput);
}

function startNumericGuard() {
    initNumericInputs();

    // Livewire mengganti sebagian DOM saat render ulang (misalnya keranjang POS).
    const observer = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    initNumericInputs(node);
                }
            });

            if (mutation.type === 'attributes' && mutation.target instanceof HTMLInputElement) {
                if (mutation.target.type === 'number') {
                    delete mutation.target.dataset.numericGuard;
                    guardNumericInput(mutation.target);
                }
            }
        }
    });

    observer.observe(document.documentElement, {
        childList: true,
        subtree: true,
        attributes: true,
        attributeFilter: ['type'],
    });

    document.addEventListener('livewire:init', () => {
        if (window.Livewire?.hook) {
            window.Livewire.hook('morph.updated', ({ el }) => initNumericInputs(el));
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startNumericGuard, { once: true });
} else {
    startNumericGuard();
}
