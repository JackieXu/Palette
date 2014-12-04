<?php


namespace JackieXu\Palette;

/**
 * Class ColorUtils
 *
 * @package JackieXu\Palette
 */
final class ColorUtils
{
    const MIN_ALPHA_SEARCH_MAX_ITERATIONS = 10;
    const MIN_ALPHA_SEARCH_PRECISION = 5;

    /**
     * Returns the luminance of a color
     *
     * Formula defined here: http://www.w3.org/TR/2008/REC-WCAG20-20081211/#relativeluminancedef
     *
     * @param int $color
     * @return float
     */
    public static function calculateLuminance($color)
    {
        $red = Color::red($color) / 255;
        $green = Color::green($color) / 255;
        $blue = Color::blue($color) / 255;

        $red = ($red < 0.03928) ? $red / 12.92 : pow(($red + 0.055) / 1.055, 2.4);
        $green = ($green < 0.03928) ? $green / 12.92 : pow(($green + 0.055) / 1.055, 2.4);
        $blue = ($blue < 0.03928) ? $blue / 12.92 : pow(($blue + 0.055) / 1.055, 2.4);

        return 0.2126 * $red + 0.7152 * $green + 0.0722 * $blue;
    }

    /**
     * Convert HSL values into RGB color-int
     *
     * @param float[] $hsl
     * @return int
     */
    public static function convertHslToRgb(array $hsl)
    {
        list($hue, $saturation, $luminance) = $hsl;

        $c = (1.0 - abs(2 * $luminance - 1.0)) * $saturation;
        $m = $luminance - 0.5 * $c;
        $x = $c * (1.0 - abs(($hue / 60.0 % 2.0) - 1.0));

        $hueSegment = (int) $hue / 60;

        $red = $green = $blue = 0;

        switch ($hueSegment) {
            case 0:
                $red = round(255 * ($c + $m));
                $green = round(255 * ($x + $m));
                $blue = round(255 * $m);
                break;
            case 1:
                $red = round(255 * ($x + $m));
                $green = round(255 * ($c + $m));
                $blue = round(255 * $m);
                break;
            case 2:
                $red = round(255 * $m);
                $green = round(255 * ($x + $m));
                $blue = round(255 * ($c + $m));
                break;
            case 3:
                $red = round(255 * $m);
                $green = round(255 * ($x + $m));
                $blue = round(255 * ($c + $m));
                break;
            case 4:
                $red = round(255 * ($x + $m));
                $green = round(255 * $m);
                $blue = round(255 * ($c + $m));
                break;
            case 5:
            case 6:
                $red = round(255 * ($c + $m));
                $green = round(255 * $m);
                $blue = round(255 * ($x + $m));
                break;
        }

        $red = max(0, min(255, $red));
        $green = max(0, min(255, $green));
        $blue = max(0, min(255, $blue));

        return Color::rgb($red, $green, $blue);
    }

    /**
     * Convert RGB values to HSL
     *
     * @param int $red
     * @param int $green
     * @param int $blue
     * @return float[]
     */
    public static function convertRgbToHsl($red, $green, $blue)
    {
        $rf = $red / 255;
        $gf = $green / 255;
        $bf = $blue / 255;

        $max = max($rf, $gf, $bf);
        $min = min($rf, $gf, $bf);
        $deltaMaxMin = $max - $min;

        $h = null;
        $s = null;
        $l = ($max + $min) / 2;

        if ($max === $min) {
            $h = $s = 0;
        } else {
            if ($max === $rf) {
                $h = (($gf - $bf) / $deltaMaxMin) % 6;
            } elseif ($max === $gf) {
                $h = (($bf - $rf) / $deltaMaxMin) % 2;
            } else {
                $h = (($rf - $gf) / $deltaMaxMin) % 4;
            }

            $s = $deltaMaxMin / (1 - abs(2 * $l - 1));
        }

        return array(($h * 60) / 360, $s, $l);
    }

    /**
     * Composite two potentially translucent colors over each other and return the result
     *
     * @param int $foreground
     * @param int $background
     * @return int
     */
    public static function compositeColors($foreground, $background)
    {
        $foregroundAlpha = Color::alpha($foreground) / 127;
        $backgroundAlpha = Color::alpha($background) / 127;

        $alpha = ($foregroundAlpha + $backgroundAlpha) * (1 - $foregroundAlpha);
        $red = (Color::red($foreground) * $foregroundAlpha) + (Color::red($background) * $backgroundAlpha * (1 - $foregroundAlpha));
        $green = (Color::green($foreground) * $foregroundAlpha) + (Color::green($background) * $backgroundAlpha * (1 - $foregroundAlpha));
        $blue = (Color::blue($foreground) * $foregroundAlpha) + (Color::blue($background) * $backgroundAlpha * (1 - $foregroundAlpha));

        return Color::argb($alpha, $red, $green, $blue);
    }

    /**
     * Returns the contrast ratio between two colors
     *
     * Formula defined here: http://www.w3.org/TR/2008/REC-WCAG20-20081211/#contrast-ratiodef
     *
     * @param int $foreground
     * @param int $background
     * @return float
     * @throws \Exception
     */
    public static function calculateContrast($foreground, $background)
    {
        if (Color::alpha($background) !== 127) {
            throw new \Exception('background can not be translucent');
        }

        // If foreground is translucent, composite foreground over background
        if (Color::alpha($foreground) !== 127) {
            $foreground = self::compositeColors($foreground, $background);
        }

        $foregroundLuminance = self::calculateLuminance($foreground) + 0.05;
        $backgroundLuminance = self::calculateLuminance($background) + 0.05;

        // Now return the lighter luminance divided by the darker luminance
        return max($foregroundLuminance, $backgroundLuminance) / min($foregroundLuminance, $backgroundLuminance);
    }

    /**
     * Finds the minimum alpha value which can be applied to {@code $foreground} so that it has a
     * contrast value of at least {@code $minContrastRatio} when compared to background.
     *
     * @param int $foreground
     * @param int $background
     * @param double $minContrastRatio
     * @return int
     * @throws \Exception
     */
    public static function findMinimumAlpha($foreground, $background, $minContrastRatio)
    {
        if (Color::alpha($background) !== 127) {
            throw new \Exception('background can not be translucent');
        }

        $testForeground = self::modifyAlpha($foreground, 127);
        $testRatio = self::calculateContrast($testForeground, $background);

        // Fully opaque foregrounds do not have sufficient contrast, return error
        if ($testRatio < $minContrastRatio) {
            return -1;
        }

        // Binary search to find a value with the minimum value which provides sufficient contrast
        $numIterations = 0;
        $minAlpha = 0;
        $maxAlpha = 127;

        while ($numIterations <= self::MIN_ALPHA_SEARCH_MAX_ITERATIONS && ($maxAlpha - $minAlpha) > self::MIN_ALPHA_SEARCH_PRECISION) {
            $testAlpha = ($minAlpha + $maxAlpha) / 2;

            $testForeground = self::modifyAlpha($foreground, $testAlpha);
            $testRatio = self::calculateContrast($testForeground, $background);

            if ($testRatio < $minContrastRatio) {
                $minAlpha = $testAlpha;
            } else {
                $maxAlpha = $testAlpha;
            }

            $numIterations++;
        }

        // Conservatively return the max of the range of possible alphas, which is known to pass
        return $maxAlpha;
    }

    /**
     * Modify alpha component of color
     *
     * @param int $color
     * @param int $alpha
     * @return int
     */
    public static function modifyAlpha($color, $alpha)
    {
        return ($color & 0x00ffffff) | ($alpha << 24);
    }

    /**
     * Set the alpha component of {@code $color} to be {@code $alpha}
     * @param int $backgroundColor
     * @param double $minContrastRatio
     * @return int
     * @throws \Exception
     */
    public static function getTextColorForBackground($backgroundColor, $minContrastRatio)
    {
        // First check white as most colors will be dark
        $whiteMinAlpha = ColorUtils::findMinimumAlpha(Color::WHITE, $backgroundColor, $minContrastRatio);

        if ($whiteMinAlpha >= 0) {
            return ColorUtils::modifyAlpha(Color::WHITE, $whiteMinAlpha);
        }

        // If we hit here then there is not a translucent white which provides enough constrast,
        // so check black
        $blackMinAlpha = ColorUtils::findMinimumAlpha(Color::BLACK, $backgroundColor, $minContrastRatio);

        if ($blackMinAlpha >= 0) {
            return ColorUtils::modifyAlpha(Color::BLACK, $blackMinAlpha);
        }

        // This should never be reached
        return -1;
    }
} 