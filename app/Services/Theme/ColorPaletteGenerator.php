<?php

declare(strict_types=1);

namespace App\Services\Theme;

use InvalidArgumentException;

final class ColorPaletteGenerator
{
    /**
     * OKLCH lightness targets for a usable 50-950 scale.
     *
     * @var array<int, float>
     */
    private const SHADE_LIGHTNESS = [
        50 => 0.970,
        100 => 0.935,
        200 => 0.885,
        300 => 0.810,
        400 => 0.705,
        500 => 0.625,
        600 => 0.550,
        700 => 0.490,
        800 => 0.430,
        900 => 0.380,
        950 => 0.280,
    ];

    /**
     * Chroma multipliers keep the lighter shades soft and the darker
     * shades rich without leaving the sRGB gamut.
     *
     * @var array<int, float>
     */
    private const SHADE_CHROMA = [
        50 => 0.12,
        100 => 0.24,
        200 => 0.44,
        300 => 0.66,
        400 => 0.86,
        500 => 0.98,
        600 => 1.00,
        700 => 0.96,
        800 => 0.88,
        900 => 0.80,
        950 => 0.60,
    ];

    /**
     * Build a full primary palette from one hex color.
     *
     * @return array<string, string>
     */
    public function generate(string $color): array
    {
        [$red, $green, $blue] = $this->parseHex($color);
        [, $chroma, $hue] = $this->rgbToOklch($red, $green, $blue);

        $palette = [];

        foreach (self::SHADE_LIGHTNESS as $shade => $lightness) {
            $palette['--color-primary-' . $shade] = $this->oklchToHex(
                $lightness,
                $chroma * self::SHADE_CHROMA[$shade],
                $hue,
            );
        }

        return $palette;
    }

    public function normalize(string $color): string
    {
        [$red, $green, $blue] = $this->parseHex($color);

        return sprintf('#%02x%02x%02x', $red, $green, $blue);
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private function parseHex(string $color): array
    {
        $hex = ltrim(trim($color), '#');

        if (preg_match('/^[0-9a-f]{3}$/i', $hex) === 1) {
            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
        }

        if (preg_match('/^[0-9a-f]{6}$/i', $hex) !== 1) {
            throw new InvalidArgumentException('Kode warna harus berupa hex 3 atau 6 digit.');
        }

        return [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function rgbToOklch(int $red, int $green, int $blue): array
    {
        $r = $this->srgbToLinear($red / 255);
        $g = $this->srgbToLinear($green / 255);
        $b = $this->srgbToLinear($blue / 255);

        $l = 0.4122214708 * $r + 0.5363325363 * $g + 0.0514459929 * $b;
        $m = 0.2119034982 * $r + 0.6806995451 * $g + 0.1073969566 * $b;
        $s = 0.0883024619 * $r + 0.2817188376 * $g + 0.6299787005 * $b;

        $lRoot = $l ** (1 / 3);
        $mRoot = $m ** (1 / 3);
        $sRoot = $s ** (1 / 3);

        $okLightness = 0.2104542553 * $lRoot + 0.7936177850 * $mRoot - 0.0040720468 * $sRoot;
        $okA = 1.9779984951 * $lRoot - 2.4285922050 * $mRoot + 0.4505937099 * $sRoot;
        $okB = 0.0259040371 * $lRoot + 0.7827717662 * $mRoot - 0.8086757660 * $sRoot;

        $chroma = sqrt($okA ** 2 + $okB ** 2);
        $hue = rad2deg(atan2($okB, $okA));

        if ($hue < 0) {
            $hue += 360;
        }

        return [$okLightness, $chroma, $hue];
    }

    private function oklchToHex(float $lightness, float $chroma, float $hue): string
    {
        $lightness = min(1.0, max(0.0, $lightness));
        $chroma = max(0.0, $chroma);

        for ($attempt = 0; $attempt < 32; $attempt++) {
            $radians = deg2rad($hue);
            [$red, $green, $blue] = $this->oklabToSrgb(
                $lightness,
                $chroma * cos($radians),
                $chroma * sin($radians),
            );

            if (
                $red >= -0.0001 && $red <= 1.0001
                && $green >= -0.0001 && $green <= 1.0001
                && $blue >= -0.0001 && $blue <= 1.0001
            ) {
                return $this->rgbToHex($red, $green, $blue);
            }

            $chroma *= 0.9;
        }

        [$red, $green, $blue] = $this->oklabToSrgb($lightness, 0.0, 0.0);

        return $this->rgbToHex($red, $green, $blue);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private function oklabToSrgb(float $lightness, float $okA, float $okB): array
    {
        $lRoot = $lightness + 0.3963377774 * $okA + 0.2158037573 * $okB;
        $mRoot = $lightness - 0.1055613458 * $okA - 0.0638541728 * $okB;
        $sRoot = $lightness - 0.0894841775 * $okA - 1.2914855480 * $okB;

        $l = $lRoot ** 3;
        $m = $mRoot ** 3;
        $s = $sRoot ** 3;

        return [
            $this->linearToSrgb(4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s),
            $this->linearToSrgb(-1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s),
            $this->linearToSrgb(-0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s),
        ];
    }

    private function rgbToHex(float $red, float $green, float $blue): string
    {
        return sprintf(
            '#%02x%02x%02x',
            (int) round($this->clamp01($red) * 255),
            (int) round($this->clamp01($green) * 255),
            (int) round($this->clamp01($blue) * 255),
        );
    }

    private function srgbToLinear(float $channel): float
    {
        return $channel <= 0.04045
            ? $channel / 12.92
            : (($channel + 0.055) / 1.055) ** 2.4;
    }

    private function linearToSrgb(float $channel): float
    {
        return $channel <= 0.0031308
            ? 12.92 * $channel
            : 1.055 * ($channel ** (1 / 2.4)) - 0.055;
    }

    private function clamp01(float $value): float
    {
        return min(1.0, max(0.0, $value));
    }
}
