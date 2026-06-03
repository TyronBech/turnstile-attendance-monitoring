<?php

namespace App\Helpers;

class ColorHelper
{
    /**
     * Generate a Tailwind-like 50-900 palette from a base hex color.
     *
     * @return array<int, string>
     */
    public static function generatePalette(string $hex): array
    {
        [$red, $green, $blue] = self::parseHex($hex);
        [$hue, $saturation, $baseLightness] = self::rgbToHsl($red, $green, $blue);

        $scale = [
            50 => 0.95,
            100 => 0.85,
            200 => 0.70,
            300 => 0.50,
            400 => 0.25,
            500 => 0.00,
            600 => -0.15,
            700 => -0.30,
            800 => -0.50,
            900 => -0.70,
        ];

        $palette = [];

        foreach ($scale as $key => $delta) {
            $lightness = $delta > 0
                ? $baseLightness + (1 - $baseLightness) * $delta
                : $baseLightness * (1 + $delta);

            $lightness = max(0, min(1, $lightness));

            $adjustedSaturation = $saturation * match (true) {
                $key <= 100 => 0.80,
                $key >= 800 => 0.90,
                default => 1.0,
            };

            [$paletteRed, $paletteGreen, $paletteBlue] = self::hslToRgb($hue, $adjustedSaturation, $lightness);
            $palette[$key] = "{$paletteRed} {$paletteGreen} {$paletteBlue}";
        }

        return $palette;
    }

    /**
     * @return array{0: float|int, 1: float|int, 2: float}
     */
    private static function rgbToHsl(int $red, int $green, int $blue): array
    {
        $red /= 255;
        $green /= 255;
        $blue /= 255;

        $max = max($red, $green, $blue);
        $min = min($red, $green, $blue);
        $lightness = ($max + $min) / 2;

        if ($max === $min) {
            return [0, 0, $lightness];
        }

        $delta = $max - $min;
        $saturation = $lightness > 0.5
            ? $delta / (2 - $max - $min)
            : $delta / ($max + $min);

        $hue = match ($max) {
            $red => ($green - $blue) / $delta + ($green < $blue ? 6 : 0),
            $green => ($blue - $red) / $delta + 2,
            default => ($red - $green) / $delta + 4,
        };

        return [$hue / 6, $saturation, $lightness];
    }

    /**
     * @return array{0: float|int, 1: float|int, 2: float|int}
     */
    private static function hslToRgb(float $hue, float $saturation, float $lightness): array
    {
        if ($saturation == 0.0) {
            $value = round($lightness * 255);

            return [$value, $value, $value];
        }

        $q = $lightness < 0.5
            ? $lightness * (1 + $saturation)
            : $lightness + $saturation - $lightness * $saturation;
        $p = 2 * $lightness - $q;

        return [
            round(self::hueToRgb($p, $q, $hue + 1 / 3) * 255),
            round(self::hueToRgb($p, $q, $hue) * 255),
            round(self::hueToRgb($p, $q, $hue - 1 / 3) * 255),
        ];
    }

    private static function hueToRgb(float $p, float $q, float $t): float
    {
        if ($t < 0) {
            $t += 1;
        }

        if ($t > 1) {
            $t -= 1;
        }

        if ($t < 1 / 6) {
            return $p + ($q - $p) * 6 * $t;
        }

        if ($t < 1 / 2) {
            return $q;
        }

        if ($t < 2 / 3) {
            return $p + ($q - $p) * (2 / 3 - $t) * 6;
        }

        return $p;
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function parseHex(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = "{$hex[0]}{$hex[0]}{$hex[1]}{$hex[1]}{$hex[2]}{$hex[2]}";
        }

        if (! preg_match('/^[0-9a-fA-F]{6}$/', $hex)) {
            $hex = '000000';
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }
}
