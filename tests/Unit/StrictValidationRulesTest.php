<?php

namespace Tests\Unit;

use App\Rules\StrictCurrency;
use App\Rules\StrictNumeric;
use App\Rules\StrictQuantity;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StrictValidationRulesTest extends TestCase
{
    // ========== STRICT CURRENCY TESTS ==========

    /** @test */
    public function strict_currency_accepts_valid_positive_numbers()
    {
        $rule = new StrictCurrency();
        
        $validInputs = ['1000', '5000.50', '123456.78', '0', '0.01'];
        
        foreach ($validInputs as $input) {
            $validator = Validator::make(['amount' => $input], ['amount' => $rule]);
            
            $this->assertFalse(
                $validator->fails(),
                "StrictCurrency should accept valid input: {$input}"
            );
        }
    }

    /** @test */
    public function strict_currency_rejects_negative_numbers()
    {
        $rule = new StrictCurrency();
        
        $invalidInputs = ['-100', '-5000.50', '-0.01'];
        
        foreach ($invalidInputs as $input) {
            $validator = Validator::make(['amount' => $input], ['amount' => $rule]);
            
            $this->assertTrue(
                $validator->fails(),
                "StrictCurrency should reject negative input: {$input}"
            );
        }
    }

    /** @test */
    public function strict_currency_rejects_special_characters()
    {
        $rule = new StrictCurrency();
        
        $invalidInputs = [
            '1000;',
            '5000,00',
            '1000+',
            '100-',
            '1e10',
            '5E3',
            '100[',
            '100]',
            '100{',
            '100}',
            'abc100',
            '100abc',
        ];
        
        foreach ($invalidInputs as $input) {
            $validator = Validator::make(['amount' => $input], ['amount' => $rule]);
            
            $this->assertTrue(
                $validator->fails(),
                "StrictCurrency should reject invalid input: {$input}"
            );
        }
    }

    /** @test */
    public function strict_currency_rejects_multiple_decimal_points()
    {
        $rule = new StrictCurrency();
        
        $validator = Validator::make(['amount' => '100.50.25'], ['amount' => $rule]);
        
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function strict_currency_respects_min_max_values()
    {
        $rule = new StrictCurrency(min: 100, max: 10000);
        
        // Below min
        $validator = Validator::make(['amount' => '50'], ['amount' => $rule]);
        $this->assertTrue($validator->fails());
        
        // Within range
        $validator = Validator::make(['amount' => '500'], ['amount' => $rule]);
        $this->assertFalse($validator->fails());
        
        // Above max
        $validator = Validator::make(['amount' => '20000'], ['amount' => $rule]);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function strict_currency_accepts_max_2_decimal_digits()
    {
        $rule = new StrictCurrency();
        
        // Valid: 2 decimals or less
        $validator = Validator::make(['amount' => '100.50'], ['amount' => $rule]);
        $this->assertFalse($validator->fails());
        
        // Invalid: more than 2 decimals
        $validator = Validator::make(['amount' => '100.505'], ['amount' => $rule]);
        $this->assertTrue($validator->fails());
    }

    // ========== STRICT QUANTITY TESTS ==========

    /** @test */
    public function strict_quantity_accepts_valid_integers()
    {
        $rule = new StrictQuantity(allowDecimal: false);
        
        $validInputs = ['1', '10', '100', '9999'];
        
        foreach ($validInputs as $input) {
            $validator = Validator::make(['qty' => $input], ['qty' => $rule]);
            
            $this->assertFalse(
                $validator->fails(),
                "StrictQuantity should accept valid integer: {$input}"
            );
        }
    }

    /** @test */
    public function strict_quantity_rejects_decimals_when_not_allowed()
    {
        $rule = new StrictQuantity(allowDecimal: false);
        
        $validator = Validator::make(['qty' => '10.5'], ['qty' => $rule]);
        
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function strict_quantity_accepts_decimals_when_allowed()
    {
        $rule = new StrictQuantity(allowDecimal: true);
        
        $validator = Validator::make(['qty' => '10.5'], ['qty' => $rule]);
        
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function strict_quantity_rejects_leading_zeros()
    {
        $rule = new StrictQuantity();
        
        $invalidInputs = ['001', '0123', '00'];
        
        foreach ($invalidInputs as $input) {
            $validator = Validator::make(['qty' => $input], ['qty' => $rule]);
            
            $this->assertTrue(
                $validator->fails(),
                "StrictQuantity should reject leading zeros: {$input}"
            );
        }
    }

    /** @test */
    public function strict_quantity_rejects_zero_and_negative()
    {
        $rule = new StrictQuantity();
        
        // Zero
        $validator = Validator::make(['qty' => '0'], ['qty' => $rule]);
        $this->assertTrue($validator->fails());
        
        // Negative
        $validator = Validator::make(['qty' => '-5'], ['qty' => $rule]);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function strict_quantity_respects_min_max_range()
    {
        $rule = new StrictQuantity(allowDecimal: false, min: 5, max: 100);
        
        // Below min
        $validator = Validator::make(['qty' => '3'], ['qty' => $rule]);
        $this->assertTrue($validator->fails());
        
        // Within range
        $validator = Validator::make(['qty' => '50'], ['qty' => $rule]);
        $this->assertFalse($validator->fails());
        
        // Above max
        $validator = Validator::make(['qty' => '200'], ['qty' => $rule]);
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function strict_quantity_rejects_special_characters()
    {
        $rule = new StrictQuantity();
        
        $invalidInputs = [
            '10;',
            '10,',
            '10+',
            '10-',
            '10e2',
            '10[',
            'abc',
        ];
        
        foreach ($invalidInputs as $input) {
            $validator = Validator::make(['qty' => $input], ['qty' => $rule]);
            
            $this->assertTrue(
                $validator->fails(),
                "StrictQuantity should reject invalid input: {$input}"
            );
        }
    }

    // ========== STRICT NUMERIC TESTS ==========

    /** @test */
    public function strict_numeric_accepts_valid_numbers()
    {
        $rule = new StrictNumeric();
        
        $validInputs = ['100', '5000.50', '0', '0.0001'];
        
        foreach ($validInputs as $input) {
            $validator = Validator::make(['value' => $input], ['value' => $rule]);
            
            $this->assertFalse(
                $validator->fails(),
                "StrictNumeric should accept valid input: {$input}"
            );
        }
    }

    /** @test */
    public function strict_numeric_accepts_negative_when_allowed()
    {
        $rule = new StrictNumeric(allowDecimal: true, allowNegative: true);
        
        $validator = Validator::make(['value' => '-100'], ['value' => $rule]);
        
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function strict_numeric_rejects_negative_when_not_allowed()
    {
        $rule = new StrictNumeric(allowDecimal: true, allowNegative: false);
        
        $validator = Validator::make(['value' => '-100'], ['value' => $rule]);
        
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function strict_numeric_rejects_scientific_notation()
    {
        $rule = new StrictNumeric();
        
        $invalidInputs = ['1e10', '5E3', '1.5e2'];
        
        foreach ($invalidInputs as $input) {
            $validator = Validator::make(['value' => $input], ['value' => $rule]);
            
            $this->assertTrue(
                $validator->fails(),
                "StrictNumeric should reject scientific notation: {$input}"
            );
        }
    }

    // ========== INTEGRATION TESTS ==========

    /** @test */
    public function validation_works_with_multiple_rules()
    {
        $rules = [
            'price' => ['required', new StrictCurrency(min: 0, max: 1000000)],
            'qty' => ['required', new StrictQuantity(allowDecimal: false, min: 1)],
            'discount' => ['nullable', new StrictCurrency(min: 0, required: false)],
        ];

        // Valid data
        $validator = Validator::make([
            'price' => '50000',
            'qty' => '10',
            'discount' => '5000',
        ], $rules);
        
        $this->assertFalse($validator->fails());

        // Invalid price (special char)
        $validator = Validator::make([
            'price' => '50000;',
            'qty' => '10',
        ], $rules);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('price', $validator->errors()->toArray());

        // Invalid qty (leading zero)
        $validator = Validator::make([
            'price' => '50000',
            'qty' => '010',
        ], $rules);
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('qty', $validator->errors()->toArray());
    }

    /** @test */
    public function empty_string_fails_when_required()
    {
        $rule = new StrictCurrency(min: 0, required: true);
        
        $validator = Validator::make(['amount' => ''], ['amount' => $rule]);
        
        $this->assertTrue($validator->fails());
    }

    /** @test */
    public function empty_string_passes_when_not_required()
    {
        $rule = new StrictCurrency(min: 0, required: false);
        
        $validator = Validator::make(['amount' => ''], ['amount' => ['nullable', $rule]]);
        
        $this->assertFalse($validator->fails());
    }

    /** @test */
    public function null_value_passes_when_nullable()
    {
        $rule = new StrictCurrency(min: 0, required: false);
        
        $validator = Validator::make(['amount' => null], ['amount' => ['nullable', $rule]]);
        
        $this->assertFalse($validator->fails());
    }

    // ========== SECURITY TESTS ==========

    /** @test */
    public function prevents_sql_injection_attempts()
    {
        $rule = new StrictCurrency();
        
        $sqlInjectionAttempts = [
            "1000' OR '1'='1",
            "1000; DROP TABLE users;--",
            "1000' UNION SELECT * FROM users--",
            "1000/**/OR/**/1=1",
        ];
        
        foreach ($sqlInjectionAttempts as $attempt) {
            $validator = Validator::make(['amount' => $attempt], ['amount' => $rule]);
            
            $this->assertTrue(
                $validator->fails(),
                "Should block SQL injection attempt: {$attempt}"
            );
        }
    }

    /** @test */
    public function prevents_xss_attempts()
    {
        $rule = new StrictCurrency();
        
        $xssAttempts = [
            '1000<script>alert("XSS")</script>',
            '1000"><img src=x onerror=alert(1)>',
            "1000'; alert('XSS');//",
        ];
        
        foreach ($xssAttempts as $attempt) {
            $validator = Validator::make(['amount' => $attempt], ['amount' => $rule]);
            
            $this->assertTrue(
                $validator->fails(),
                "Should block XSS attempt: {$attempt}"
            );
        }
    }

    /** @test */
    public function prevents_unicode_exploits()
    {
        $rule = new StrictCurrency();
        
        // Unicode characters that look like numbers
        $unicodeAttempts = [
            '１０００', // Full-width digits
            '1⁰⁰⁰', // Superscript
            '₁₀₀₀', // Subscript
        ];
        
        foreach ($unicodeAttempts as $attempt) {
            $validator = Validator::make(['amount' => $attempt], ['amount' => $rule]);
            
            $this->assertTrue(
                $validator->fails(),
                "Should block unicode exploit: {$attempt}"
            );
        }
    }
}
