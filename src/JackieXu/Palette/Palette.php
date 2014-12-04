<?php


namespace JackieXu\Palette;

/**
 * Class Palette
 *
 * A helper class to extract prominent colors from an image.
 *
 * @package JackieXu\Palette
 */
class Palette
{
    const DEFAULT_CALCULATE_NUMBER_COLORS = 16;

    const MIN_CONTRAST_TITLE_TEXT = 3.0;
    const MIN_CONTRAST_BODY_TEXT = 4.5;

    const TARGET_DARK_LUMA = 0.26;
    const MAX_DARK_LUMA = 0.45;

    const MIN_LIGHT_LUMA = 0.55;
    const TARGET_LIGHT_LUMA = 0.74;

    const MIN_NORMAL_LUMA = 0.3;
    const TARGET_NORMAL_LUMA = 0.5;
    const MAX_NORMAL_LUMA = 0.7;

    const TARGET_MUTED_SATURATION = 0.3;
    const MAX_MUTED_SATURATION = 0.4;

    const TARGET_VIBRANT_SATURATION = 1;
    const MIN_VIBRANT_SATURATION = 0.35;

    const WEIGHT_SATURATION = 3;
    const WEIGHT_LUMA = 6;
    const WEIGHT_POPULATION = 1;

    /**
     * @var Swatch[]
     */
    protected $swatches;

    /**
     * @var int
     */
    protected $highestPopulation;

    /**
     * @var Swatch
     */
    protected $vibrantSwatch;

    /**
     * @var Swatch
     */
    protected $mutedSwatch;

    /**
     * @var Swatch
     */
    protected $darkVibrantSwatch;

    /**
     * @var Swatch
     */
    protected $darkMutedSwatch;

    /**
     * @var Swatch
     */
    protected $lightVibrantSwatch;

    /**
     * @var Swatch
     */
    protected $lightMutedSwatch;


    /**
     * Palette constructor
     *
     * Start off by find the color for our six target swatches.
     *
     * @param Swatch[] $swatches
     */
    public function __construct(array $swatches)
    {
        $this->swatches = $swatches;
        $this->highestPopulation = $this->findMaxPopulation();

        $this->vibrantSwatch = $this->findColor(
            self::TARGET_NORMAL_LUMA, self::MIN_NORMAL_LUMA, self::MAX_NORMAL_LUMA,
            self::TARGET_VIBRANT_SATURATION, self::MIN_VIBRANT_SATURATION, 1.0
        );

        $this->lightVibrantSwatch = $this->findColor(
            self::TARGET_LIGHT_LUMA, self::MIN_LIGHT_LUMA, 1.0,
            self::TARGET_VIBRANT_SATURATION, self::MIN_VIBRANT_SATURATION, 1.0
        );

        $this->darkVibrantSwatch = $this->findColor(
            self::TARGET_DARK_LUMA, 0.0, self::MAX_DARK_LUMA,
            self::TARGET_VIBRANT_SATURATION, self::MIN_VIBRANT_SATURATION, 1.0
        );

        $this->mutedSwatch = $this->findColor(
            self::TARGET_NORMAL_LUMA, self::MIN_NORMAL_LUMA, self::MAX_NORMAL_LUMA,
            self::TARGET_MUTED_SATURATION, 0.0, self::MAX_MUTED_SATURATION
        );

        $this->lightMutedSwatch = $this->findColor(
            self::TARGET_LIGHT_LUMA, self::MIN_LIGHT_LUMA, 1.0,
            self::TARGET_MUTED_SATURATION, 0.0, self::MAX_MUTED_SATURATION
        );

        $this->darkMutedSwatch = $this->findColor(
            self::TARGET_DARK_LUMA, 0.0, self::MAX_DARK_LUMA,
            self::TARGET_MUTED_SATURATION, 0.0, self::MAX_MUTED_SATURATION
        );

        $this->generateEmptySwatches();
    }

    /**
     * Try and generate any missing swatches from the swatches we did find
     */
    private function generateEmptySwatches()
    {
        // If we don't have a vibrant color
        if (is_null($this->vibrantSwatch)) {
            // but do have a dark vibrant color, generate the value by modifying lumination
            if (!is_null($this->darkVibrantSwatch)) {
                $newHsl = $this->copyHslValues($this->darkVibrantSwatch);
                $newHsl[2] = self::TARGET_NORMAL_LUMA;
                $this->vibrantSwatch = new Swatch(ColorUtils::convertHsltoRgb($newHsl), 0);
            }
        }

        // If we don't have a dark vibrant color
        if (is_null($this->darkVibrantSwatch)) {
            // but do have a vibrant color, generate the value by modifying lumination
            if (!is_null($this->vibrantSwatch)) {
                $newHsl = $this->copyHslValues($this->vibrantSwatch);
                $newHsl[2] = self::TARGET_DARK_LUMA;
                $this->darkVibrantSwatch = new Swatch(ColorUtils::convertHslToRgb($newHsl), 0);
            }
        }
    }

    /**
     * Copy a {@link Swatch}'s HSL values into a new array
     *
     * @param Swatch $color
     * @return array
     */
    private static function copyHslValues(Swatch $color)
    {
        $newHsl = new \SplFixedArray(3);
        list($newHsl[0], $newHsl[1], $newHsl[2]) = $color->getHsl();
        return $newHsl->toArray();
    }

    /**
     * Find the {@link Swatch} with the highest population value and return the population
     *
     * @return int
     */
    public function findMaxPopulation()
    {
        $population = 0;

        foreach ($this->swatches as $swatch) {
            $population = max($population, $swatch->getPopulation());
        }

        return $population;
    }

    /**
     * Returns the most vibrant swatch in the palette. Might be null.
     *
     * @return Swatch
     */
    public function getVibrantSwatch()
    {
        return $this->vibrantSwatch;
    }

    /**
     * Returns a muted swatch from the palette. Might be null.
     *
     * @return Swatch
     */
    public function getMutedSwatch()
    {
        return $this->mutedSwatch;
    }

    /**
     * Returns a dark and vibrant swatch from the palette. Might be null.
     *
     * @return Swatch
     */
    public function getDarkVibrantSwatch()
    {
        return $this->darkVibrantSwatch;
    }

    /**
     * Returns a muted and dark swatch from the palette. Might be null.
     *
     * @return Swatch
     */
    public function getDarkMutedSwatch()
    {
        return $this->darkMutedSwatch;
    }

    /**
     * Returns a light and vibrant swatch from the palette. Might be null.
     *
     * @return Swatch
     */
    public function getLightVibrantSwatch()
    {
        return $this->lightVibrantSwatch;
    }

    /**
     * Returns a muted and light swatch from the palette. Might be null.
     *
     * @return Swatch
     */
    public function getLightMutedSwatch()
    {
        return $this->lightMutedSwatch;
    }

    /**
     * Returns the most vibrant color in the palette as an RGB packed int.
     *
     * @param int $defaultColor
     * @return int
     */
    public function getVibrantColor($defaultColor)
    {
        return is_null($this->vibrantSwatch) ? $defaultColor : $this->vibrantSwatch->getRgb();
    }

    /**
     * Returns a light and vibrant color from the palette as an RGB packed int.
     *
     * @param int $defaultColor
     * @return int
     */
    public function getLightVibrantColor($defaultColor)
    {
        return is_null($this->lightVibrantSwatch) ? $defaultColor : $this->lightVibrantSwatch->getRgb();
    }

    /**
     * Returns a dark and vibrant color from the palette as an RGB packed int.
     *
     * @param int $defaultColor
     * @return int
     */
    public function getDarkVibrantColor($defaultColor)
    {
        return is_null($this->darkVibrantSwatch) ? $defaultColor : $this->darkVibrantSwatch->getRgb();
    }

    /**
     * Returns a muted color from the palette as an RGB packed int.
     *
     * @param int $defaultColor
     * @return int
     */
    public function getMutedColor($defaultColor)
    {
        return is_null($this->mutedSwatch) ? $defaultColor : $this->mutedSwatch->getRgb();
    }

    /**
     * Returns a muted and light color from the palette as an RGB packed int.
     *
     * @param int $defaultColor
     * @return int
     */
    public function getLightMutedColor($defaultColor)
    {
        return is_null($this->lightMutedSwatch) ? $defaultColor : $this->lightMutedSwatch->getRgb();
    }

    /**
     * Returns a muted and dark color from the palette as an RGB packed int.
     *
     * @param int $defaultColor
     * @return int
     */
    public function getDarkMutedColor($defaultColor)
    {
        return is_null($this->darkMutedSwatch) ? $defaultColor : $this->darkMutedSwatch->getRgb();
    }

    /**
     * Returns true if we have already selected {@code $swatch}
     *
     * @param Swatch $swatch
     * @return bool
     */
    private function isAlreadySelected(Swatch $swatch)
    {
        return $this->vibrantSwatch === $swatch || $this->darkVibrantSwatch === $swatch ||
                $this->lightVibrantSwatch === $swatch || $this->mutedSwatch === $swatch ||
                $this->darkMutedSwatch === $swatch || $this->lightMutedSwatch === $swatch;
    }

    /**
     * Find a color with given parameters
     *
     * @param float $targetLuma
     * @param float $minLuma
     * @param float $maxLuma
     * @param float $targetSaturation
     * @param float $minSaturation
     * @param float $maxSaturation
     * @return Swatch
     */
    private function findColor($targetLuma, $minLuma, $maxLuma, $targetSaturation, $minSaturation, $maxSaturation)
    {
        $max = null;
        $maxValue = 0.0;

        foreach ($this->swatches as $swatch) {
            $saturation = $swatch->getHsl()[1];
            $luminance = $swatch->getHsl()[2];

            if ($saturation >= $minSaturation && $saturation <= $maxSaturation &&
                $luminance >= $minLuma && $luminance <= $maxLuma && !$this->isAlreadySelected($swatch)) {
                $thisValue = self::createComparisonValue($saturation, $targetSaturation, $luminance, $targetLuma,
                    $swatch->getPopulation(), $this->highestPopulation);

                if (is_null($max) || $thisValue > $maxValue) {
                    $max = $swatch;
                    $maxValue = $thisValue;
                }
            }
        }

        return $max;
    }

    /**
     * Returns a value in the range 0-1.
     *
     * 1 is returned when {@code $value} equals the {@code $targetValue} and then decreases
     * as the absolute difference between {@code $value} and {@code $targetValue} increases.
     *
     * @param float $value
     * @param float $targetValue
     * @return float
     */
    private static function invertDiff($value, $targetValue)
    {
        return 1.0 - abs($value - $targetValue);
    }

    /**
     * Return weighted mean
     *
     * @param array $values
     * @return float
     */
    private static function weightedMean(array $values)
    {
        $sum = 0;
        $sumWeight = 0;

        for ($i = 0; $i < count($values); $i += 2) {
            $value = $values[$i];
            $weight = $values[$i + 1];

            $sum += $value * $weight;
            $sumWeight += $weight;
        }

        return $sum / $sumWeight;
    }

    /**
     * Create comparison value
     *
     * @param float $saturation
     * @param float $targetSaturation
     * @param float $luma
     * @param float $targetLuma
     * @param int $population
     * @param int $highestPopulation
     * @return float
     */
    private static function createComparisonValue($saturation, $targetSaturation, $luma, $targetLuma, $population, $highestPopulation)
    {
        return self::weightedMean(array(
            self::invertDiff($saturation, $targetSaturation), self::WEIGHT_SATURATION,
            self::invertDiff($luma, $targetLuma), self::WEIGHT_LUMA,
            $population / $highestPopulation, self::WEIGHT_POPULATION
        ));
    }
} 