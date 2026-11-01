<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Rules\StrictCurrency;
use App\Rules\StrictNumeric;
use App\Rules\StrictQuantity;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

final class StrictInputRulesTest extends TestCase
{
    public function test_currency_rejects_invalid_characters(): void
    {
        $invalidValues = [
            '12a',
            '-5',
            '+5',
            '1,000',
            '1.2.3',
            '12;',
            '[12]',
            '12 34',
            '1e3',
            'Rp 12000',
            '12/3',
            '12+3',
            '12_3',
        ];

        foreach ($invalidValues as $value) {
            $validator = Validator::make(
                ['amount' => $value],
                ['amount' => [new StrictCurrency(min: 0)]]
            );

            $this->assertTrue($validator->fails(), "Nominal '{$value}' seharusnya ditolak.");
        }
    }

    public function test_currency_accepts_clean_positive_numbers(): void
    {
        foreach (['0', '15000', '15000.50', '999999999999'] as $value) {
            $validator = Validator::make(
                ['amount' => $value],
                ['amount' => [new StrictCurrency(min: 0)]]
            );

            $this->assertFalse($validator->fails(), "Nominal '{$value}' seharusnya diterima.");
        }
    }

    public function test_quantity_rejects_invalid_characters_and_decimals_when_disallowed(): void
    {
        foreach (['2a', '-2', '1,5', '1.5', '2;', '[2]', '2 3'] as $value) {
            $validator = Validator::make(
                ['qty' => $value],
                ['qty' => [new StrictQuantity(allowDecimal: false)]]
            );

            $this->assertTrue($validator->fails(), "Jumlah '{$value}' seharusnya ditolak.");
        }

        $decimalValidator = Validator::make(
            ['qty' => '1.5'],
            ['qty' => [new StrictQuantity(allowDecimal: true)]]
        );

        $this->assertFalse($decimalValidator->fails());
    }

    public function test_numeric_rule_rejects_invalid_characters(): void
    {
        foreach (['12a', '-12', '1,5', '1.2.3', '12;', '[12]', '1e3'] as $value) {
            $validator = Validator::make(
                ['value' => $value],
                ['value' => [new StrictNumeric(allowDecimal: true, min: 0)]]
            );

            $this->assertTrue($validator->fails(), "Angka '{$value}' seharusnya ditolak.");
        }
    }
}
