<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Strict Quantity Validation Rule
 * 
 * Validasi khusus untuk input quantity/jumlah barang.
 * Hanya menerima integer positif (atau float positif jika allowDecimal=true).
 */
class StrictQuantity implements ValidationRule
{
    protected bool $allowDecimal;
    protected float $min;
    protected ?float $max;

    /**
     * @param bool $allowDecimal Izinkan decimal (untuk unit seperti kg, liter)
     * @param float $min Nilai minimum (default: 1)
     * @param float|null $max Nilai maksimum
     */
    public function __construct(
        bool $allowDecimal = false,
        float $min = 1,
        ?float $max = null
    ) {
        $this->allowDecimal = $allowDecimal;
        $this->min = $min;
        $this->max = $max;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Cek required
        if ($value === null || $value === '') {
            $fail("Jumlah :attribute harus diisi.");
            return;
        }

        // Konversi ke string
        $stringValue = trim((string) $value);

        // Cek panjang
        if (strlen($stringValue) > 10) {
            $fail("Jumlah :attribute terlalu besar.");
            return;
        }

        // Cek karakter berbahaya
        if ($this->containsInvalidCharacters($stringValue)) {
            $fail("Jumlah :attribute hanya boleh berisi angka" . 
                ($this->allowDecimal ? " dan titik desimal" : "") . ".");
            return;
        }

        // Validasi format
        $pattern = $this->allowDecimal 
            ? '/^\d+(\.\d+)?$/'  // integer atau decimal
            : '/^\d+$/';          // integer only

        if (!preg_match($pattern, $stringValue)) {
            $fail("Jumlah :attribute harus berupa " . 
                ($this->allowDecimal ? "angka positif" : "bilangan bulat positif") . ".");
            return;
        }

        // Cek leading zeros (tidak boleh: 001, 0123, dll)
        if (preg_match('/^0\d/', $stringValue)) {
            $fail("Jumlah :attribute tidak boleh dimulai dengan 0.");
            return;
        }

        // Konversi ke numeric
        $numericValue = $this->allowDecimal ? (float) $stringValue : (int) $stringValue;

        // Validasi positif
        if ($numericValue <= 0) {
            $fail("Jumlah :attribute harus lebih dari 0.");
            return;
        }

        // Validasi minimum
        if ($numericValue < $this->min) {
            $fail("Jumlah :attribute minimal {$this->min}.");
            return;
        }

        // Validasi maksimum
        if ($this->max !== null && $numericValue > $this->max) {
            $fail("Jumlah :attribute maksimal {$this->max}.");
            return;
        }

        // Untuk decimal, validasi max 4 digit desimal
        if ($this->allowDecimal && str_contains($stringValue, '.')) {
            $decimalPart = explode('.', $stringValue)[1];
            if (strlen($decimalPart) > 4) {
                $fail("Jumlah :attribute maksimal 4 digit desimal.");
                return;
            }
        }
    }

    /**
     * Cek karakter yang tidak valid
     */
    protected function containsInvalidCharacters(string $value): bool
    {
        // Karakter yang diblokir
        $blockedChars = [
            '-', '+',           // minus/plus
            'e', 'E',          // scientific notation
            ',',               // comma
            ' ',               // space
            ';', ':',
            '!', '@', '#', '$', '%', '^', '&', '*',
            '(', ')', '[', ']', '{', '}',
            '<', '>', '=',
            '/', '\\', '|',
            '~', '`',
            '"', "'",
            '_',
        ];

        foreach ($blockedChars as $char) {
            if (str_contains($value, $char)) {
                return true;
            }
        }

        // Jika tidak allow decimal, block titik
        if (!$this->allowDecimal && str_contains($value, '.')) {
            return true;
        }

        // Cek multiple dots
        if (substr_count($value, '.') > 1) {
            return true;
        }

        // Cek non-ASCII characters
        if (!mb_check_encoding($value, 'ASCII')) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Jumlah :attribute tidak valid.';
    }
}
