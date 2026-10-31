<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Strict Currency Validation Rule
 * 
 * Validasi khusus untuk input nominal uang.
 * Hanya menerima angka positif tanpa karakter spesial.
 */
class StrictCurrency implements ValidationRule
{
    protected ?float $min;
    protected ?float $max;
    protected bool $required;

    /**
     * @param float|null $min Nilai minimum (default: 0)
     * @param float|null $max Nilai maksimum
     * @param bool $required Wajib diisi (default: true)
     */
    public function __construct(
        ?float $min = 0,
        ?float $max = null,
        bool $required = true
    ) {
        $this->min = $min ?? 0;
        $this->max = $max;
        $this->required = $required;
    }

    /**
     * Run the validation rule.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Cek required
        if ($value === null || $value === '') {
            if ($this->required) {
                $fail("Nominal :attribute harus diisi.");
            }
            return;
        }

        // Konversi ke string
        $stringValue = (string) $value;

        // Sanitasi: hapus whitespace
        $stringValue = trim($stringValue);

        // Cek panjang maksimal (mencegah overflow)
        if (strlen($stringValue) > 15) {
            $fail("Nominal :attribute terlalu besar.");
            return;
        }

        // Cek karakter berbahaya
        if ($this->containsDangerousCharacters($stringValue)) {
            $fail("Nominal :attribute mengandung karakter yang tidak diperbolehkan.");
            return;
        }

        // Validasi format: hanya angka dan maksimal 1 titik desimal
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $stringValue)) {
            $fail("Nominal :attribute harus berupa angka positif dengan maksimal 2 desimal.");
            return;
        }

        // Konversi ke float
        $numericValue = (float) $stringValue;

        // Validasi range
        if ($numericValue < 0) {
            $fail("Nominal :attribute tidak boleh negatif.");
            return;
        }

        if ($numericValue < $this->min) {
            $formattedMin = number_format($this->min, 0, ',', '.');
            $fail("Nominal :attribute minimal Rp {$formattedMin}.");
            return;
        }

        if ($this->max !== null && $numericValue > $this->max) {
            $formattedMax = number_format($this->max, 0, ',', '.');
            $fail("Nominal :attribute maksimal Rp {$formattedMax}.");
            return;
        }

        // Validasi tambahan: cegah nilai yang tidak masuk akal
        if ($numericValue > 999999999999) { // 999 miliar
            $fail("Nominal :attribute terlalu besar.");
            return;
        }
    }

    /**
     * Deteksi karakter berbahaya
     */
    protected function containsDangerousCharacters(string $value): bool
    {
        // Daftar karakter yang TIDAK diperbolehkan dalam currency input
        $blockedChars = [
            '-', '+',           // operator matematika
            'e', 'E',          // scientific notation
            ',',               // thousand separator (harus sudah dibersihkan di frontend)
            ';', ':',          // punctuation berbahaya
            '!', '@', '#', '$', '%', '^', '&', '*',  // special symbols
            '(', ')', '[', ']', '{', '}',  // brackets
            '<', '>', '=',     // comparison operators
            '/', '\\', '|',    // slashes
            '~', '`',          // tilde, backtick
            '"', "'",          // quotes
            ' ',               // space (seharusnya sudah di-trim)
            '_',               // underscore
        ];

        foreach ($blockedChars as $char) {
            if (str_contains($value, $char)) {
                return true;
            }
        }

        // Cek apakah ada karakter non-ASCII (kemungkinan unicode exploit)
        if (!mb_check_encoding($value, 'ASCII')) {
            return true;
        }

        // Cek multiple dots
        if (substr_count($value, '.') > 1) {
            return true;
        }

        return false;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'Nominal :attribute tidak valid.';
    }
}
