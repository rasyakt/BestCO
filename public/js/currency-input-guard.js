/**
 * Currency Input Guard v2.0
 * Memperketat validasi input untuk form nominal uang
 * Compatible dengan: Livewire, Alpine.js, vanilla JS
 */

(function() {
    'use strict';

    // Selector untuk input yang berhubungan dengan uang
    const currencyInputSelectors = [
        'input[name*="price"]',
        'input[name*="amount"]',
        'input[name*="total"]',
        'input[name*="discount"]',
        'input[name*="paid"]',
        'input[name*="nominal"]',
        'input[data-currency="true"]',
        'input.currency-input'
    ];

    // Selector untuk input quantity/qty (hanya integer positif)
    const quantityInputSelectors = [
        'input[name*="qty"]',
        'input[name*="quantity"]',
        'input[name*="jumlah"]',
        'input[data-quantity="true"]',
        'input.quantity-input'
    ];

    let processedInputs = new WeakSet();

    function isQtyInput(input) {
        return quantityInputSelectors.some(selector => input.matches(selector));
    }

    function isCurrencyInput(input) {
        return currencyInputSelectors.some(selector => input.matches(selector));
    }

    function sanitizeValue(value, isQty) {
        if (!value) return '';
        
        let cleaned = String(value);
        
        // Hapus semua karakter selain angka dan titik
        cleaned = cleaned.replace(/[^\d.]/g, '');
        
        // Untuk qty, hapus titik juga
        if (isQty) {
            cleaned = cleaned.replace(/\./g, '');
        } else {
            // Untuk currency, pastikan hanya 1 titik
            const parts = cleaned.split('.');
            if (parts.length > 2) {
                cleaned = parts[0] + '.' + parts.slice(1).join('');
            }
        }
        
        return cleaned;
    }

    function applyProtection(input) {
        // Skip jika sudah diproses
        if (processedInputs.has(input)) return;
        processedInputs.add(input);

        const isQty = isQtyInput(input);
        const isCurrency = isCurrencyInput(input);
        
        if (!isQty && !isCurrency) return;

        // Set attributes
        input.setAttribute('inputmode', isQty ? 'numeric' : 'decimal');
        input.setAttribute('pattern', '[0-9]*');

        // Keydown - cegah karakter tidak valid
        input.addEventListener('keydown', function(e) {
            const key = e.key;
            const keyCode = e.keyCode || e.which;
            
            // Allow: backspace, delete, tab, escape, enter, arrows
            const allowedKeys = ['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 
                                'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];
            if (allowedKeys.includes(key)) return;
            
            // Allow: Ctrl+A, Ctrl+C, Ctrl+V, Ctrl+X, Ctrl+Z
            if ((e.ctrlKey || e.metaKey) && ['a','c','v','x','z'].includes(key.toLowerCase())) {
                return;
            }
            
            // Untuk currency, allow satu titik desimal
            if (!isQty && key === '.' && !input.value.includes('.')) {
                return;
            }
            
            // Block semua selain angka 0-9
            if (!/^[0-9]$/.test(key)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        });

        // Input - sanitasi real-time
        input.addEventListener('input', function(e) {
            const originalValue = input.value;
            const cleanedValue = sanitizeValue(originalValue, isQty);
            
            if (originalValue !== cleanedValue) {
                input.value = cleanedValue;
                
                // Trigger change untuk Livewire/Alpine
                const event = new Event('input', { bubbles: true });
                input.dispatchEvent(event);
            }
        });

        // Paste - sanitasi data paste
        input.addEventListener('paste', function(e) {
            e.preventDefault();
            
            const pastedData = (e.clipboardData || window.clipboardData).getData('text');
            const cleanedData = sanitizeValue(pastedData, isQty);
            
            if (cleanedData) {
                // Insert cleaned data at cursor
                const start = input.selectionStart;
                const end = input.selectionEnd;
                const currentValue = input.value;
                
                input.value = currentValue.substring(0, start) + cleanedData + currentValue.substring(end);
                
                // Trigger events untuk Livewire
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
                
                // Set cursor position
                const newPosition = start + cleanedData.length;
                input.setSelectionRange(newPosition, newPosition);
            }
        });

        // Blur - validasi final
        input.addEventListener('blur', function(e) {
            let value = input.value.trim();
            
            if (!value) return;
            
            // Sanitize one more time
            value = sanitizeValue(value, isQty);
            
            // Remove trailing dot
            value = value.replace(/\.$/, '');
            
            // Validate min/max
            const numValue = parseFloat(value) || 0;
            const minValue = parseFloat(input.getAttribute('min')) || 0;
            const maxValue = parseFloat(input.getAttribute('max'));
            
            if (numValue < minValue) {
                value = String(minValue);
            }
            
            if (maxValue && numValue > maxValue) {
                value = String(maxValue);
            }
            
            if (input.value !== value) {
                input.value = value;
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    function scanAndProtect() {
        const allSelectors = currencyInputSelectors.concat(quantityInputSelectors).join(',');
        const inputs = document.querySelectorAll(allSelectors);
        
        inputs.forEach(input => {
            if (input.type === 'number' || input.type === 'text') {
                applyProtection(input);
            }
        });
    }

    // Initial scan
    function init() {
        scanAndProtect();
        
        // Observer untuk dynamic content
        const observer = new MutationObserver(function(mutations) {
            let shouldScan = false;
            
            mutations.forEach(function(mutation) {
                if (mutation.addedNodes.length > 0) {
                    shouldScan = true;
                }
            });
            
            if (shouldScan) {
                scanAndProtect();
            }
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }

    // Initialize berdasarkan state dokumen
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Livewire compatibility
    document.addEventListener('livewire:navigated', scanAndProtect);
    document.addEventListener('livewire:load', scanAndProtect);
    
    // Livewire v3
    if (window.Livewire) {
        document.addEventListener('livewire:init', () => {
            window.Livewire.hook('morph.updated', () => {
                setTimeout(scanAndProtect, 50);
            });
            
            window.Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                succeed(({ snapshot, effect }) => {
                    setTimeout(scanAndProtect, 50);
                });
            });
        });
    }

    // Alpine.js compatibility
    document.addEventListener('alpine:initialized', scanAndProtect);

})();
