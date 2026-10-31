<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Strict Numeric Validation Rule
 * 
 * Memastikan input adalah angka murni tanpa karakter spesial.
 * Lebih ketat dari 'numeric' bawaan Laravel.
 */
class StrictNumeric implements ValidationRule
{
    protected bool $allowDecimal;
    protected bool $allowNegative;
    protected ?float $min;
    protected ?float $max;

    /**
     * @param bool $allowDecimal Izinkan angka desimal (default: true)
     * @param bool $allowNegative Izinkan angka negatif (default: false)
     * @param float|null $min Nilai minimum
     * @param float|null $max Nilai maksimum
     */
    public function __construct(
        bool $allowDecimal = true,
        bool $allowNegative = false,
        ?float $min = null,
        ?float $max = null
    ) {
        $this->allowDecimal = $allowDecimal;
        $this->allowNegative = $allowNegative;
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Cek apakah null atau empty
        if ($value === null || $value === '') {
            $fail("Field :attribute harus diisi.");
            return;
        }

        // Konversi ke string untuk validasi
        $stringValue = (string) $value;

        // Cek karakter yang tidak diinginkan
        if ($this->containsInvalidCharacters($stringValue)) {
            $fail("Field :attribute hanya boleh berisi angka" . 
                ($this->allowDecimal ? " dan titik desimal" : "") . ".");
            return;
        }

        // Validasi format angka
        if (!$this->isValidNumericFormat($stringValue)) {
            $fail("Field :attribute harus berupa angka yang valid.");
            return;
        }

        // Konversi ke numeric value
        $numericValue = $this->allowDecimal ? (float) $value : (int) $value;

        // Validasi angka negatif
        if (!$this->allowNegative && $numericValue < 0) {
            $fail("Field :attribute tidak boleh bernilai negatif.");
            return;
        }

        // Validasi minimum
        if ($this->min !== null && $numericValue < $this->min) {
            $fail("Field :attribute minimal harus {$this->min}.");
            return;
        }

        // Validasi maksimum
        if ($this->max !== null && $numericValue > $this->max) {
            $fail("Field :attribute maksimal {$this->max}.");
            return;
        }
    }

    /**
     * Cek apakah string mengandung karakter yang tidak valid
     */
    protected function containsInvalidCharacters(string $value): bool
    {
        // Daftar karakter berbahaya yang sering digunakan untuk exploit
        $dangerousChars = [
            '-' => !$this->allowNegative, // minus (kecuali allowNegative)
            '+',  // plus
            'e', 'E',  // scientific notation
            ',',  // koma (thousand separator)
            ' ',  // spasi
            ';',  // semicolon
            '[', ']',  // brackets
            '{', '}',  // braces
            '(', ')',  // parentheses
            '<', '>',  // comparison
            '=',  // equals
            '*', '/',  // operators
            '%',  // percent
            '!',  // exclamation
            '@', '#', '$',  // symbols
            '&', '|',  // logical operators
            '^',  // caret
            '~', '`',  // tilde, backtick
            '\\',  // backslash
            '"', "'",  // quotes
        ];

        foreach ($dangerousChars as $char => $shouldBlock) {
            // Jika key adalah integer, char ada di value (array indexed)
            $charToCheck = is_int($char) ? $shouldBlock : $char;
            $block = is_int($char) ? true : $shouldBlock;
            
            if ($block && str_contains($value, $charToCheck)) {
                return true;
            }
        }

        // Cek titik desimal jika tidak diizinkan
        if (!$this->allowDecimal && str_contains($value, '.')) {
            return true;
        }

        return false;
    }

    /**
     * Validasi format angka
     */
    protected function isValidNumericFormat(string $value): bool
    {
        // Pattern dasar: optional minus, angka, optional desimal
        $pattern = $this->allowNegative ? '/^-?\d+(\.\d+)?$/' : '/^\d+(\.\d+)?$/';
        
        // Untuk integer only
        if (!$this->allowDecimal) {
            $pattern = $this->allowNegative ? '/^-?\d+$/' : '/^\d+$/';
        }

        if (!preg_match($pattern, $value)) {
            return false;
        }

        // Validasi tambahan: tidak boleh multiple dots
        if ($this->allowDecimal && substr_count($value, '.') > 1) {
            return false;
        }

        // Tidak boleh leading zeros (kecuali 0 atau 0.x)
        if (preg_match('/^0\d+/', $value)) {
            return false;
        }

        // Pastikan bisa di-cast ke numeric tanpa error
        if (!is_numeric($value)) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Field :attribute harus berupa angka yang valid.';
    }
}
