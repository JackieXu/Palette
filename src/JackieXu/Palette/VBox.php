<?php


namespace JackieXu\Palette;

/**
 * Class VBox
 *
 * Represents a tightly fitting box around a color space.
 *
 * @package JackieXu\Palette
 */
class VBox
{
    /**
     * @var ColorCutQuantizer
     */
    protected $colorCutQuantizer;

    /**
     * @var int
     */
    protected $lowerIndex;

    /**
     * @var int
     */
    protected $upperIndex;

    /**
     * @var int
     */
    protected $minRed;

    /**
     * @var int
     */
    protected $minGreen;

    /**
     * @var int
     */
    protected $minBlue;

    /**
     * @var int
     */
    protected $maxRed;

    /**
     * @var int
     */
    protected $maxGreen;

    /**
     * @var int
     */
    protected $maxBlue;

    /**
     * VBox constructor
     *
     * @param ColorCutQuantizer $colorCutQuantizer
     * @param int $lowerIndex
     * @param int $upperIndex
     */
    public function __construct(ColorCutQuantizer $colorCutQuantizer, $lowerIndex, $upperIndex)
    {
        $this->colorCutQuantizer = $colorCutQuantizer;
        $this->lowerIndex = $lowerIndex;
        $this->upperIndex = $upperIndex;
        $this->fitBox();
    }

    /**
     * Get box volume
     *
     * @return int
     */
    public function getVolume()
    {
        return ($this->maxRed - $this->minRed + 1) * ($this->maxGreen - $this->minGreen + 1) * ($this->maxBlue - $this->minBlue + 1);
    }

    /**
     * Check whether box can be split
     *
     * @return bool
     */
    public function canSplit()
    {
        return $this->getColorCount() > 1;
    }

    /**
     * Get amount of colors withing the box
     *
     * @return int
     */
    public function getColorCount()
    {
        return $this->upperIndex - $this->lowerIndex + 1;
    }

    /**
     * Computes the boundaries of the box to tightly fix the colors within the box.
     */
    public function fitBox()
    {
        // Reset min and max to opposite values
        $this->minRed = $this->minGreen = $this->minBlue = 255;
        $this->maxRed = $this->maxGreen = $this->maxBlue = 0;

        for ($i = $this->lowerIndex; $i <= $this->upperIndex; $i++) {
            $color = $this->colorCutQuantizer->getColors()[$i];
            $red = Color::red($color);
            $green = Color::green($color);
            $blue = Color::blue($color);

            if ($red > $this->maxRed) {
                $this->maxRed = $red;
            }
            if ($red < $this->minRed) {
                $this->minRed = $red;
            }
            if ($green > $this->maxGreen) {
                $this->maxGreen = $green;
            }
            if ($green < $this->minGreen) {
                $this->minGreen = $green;
            }
            if ($blue > $this->maxBlue) {
                $this->maxBlue = $blue;
            }
            if ($blue < $this->minBlue) {
                $this->minBlue = $blue;
            }
        }
    }

    /**
     * Split this color box at the mid-point along it's longest dimension
     *
     * @return VBox
     * @throws \Exception
     */
    public function splitBox()
    {
        if (!$this->canSplit()) {
            throw new \Exception('can not split a box with only 1 color');
        }

        // Find median along the longest dimension
        $splitPoint = $this->findSplitPoint();

        $newBox = new VBox($this->colorCutQuantizer, $splitPoint + 1, $this->upperIndex);

        // Now change this box's upperIndex and recompute the color boundaries
        $this->upperIndex = $splitPoint;
        $this->fitBox();

        return $newBox;
    }

    /**
     * Return the dimension which this box is largest in.
     *
     * @return int
     */
    public function getLongestColorDimension()
    {
        $redLength = $this->maxRed - $this->minRed;
        $greenLength = $this->maxGreen - $this->minGreen;
        $blueLength = $this->maxBlue - $this->minBlue;

        if ($redLength >= $greenLength && $redLength >= $blueLength) {
            return ColorCutQuantizer::COMPONENT_RED;
        } elseif ($greenLength >= $redLength && $greenLength >= $blueLength) {
            return ColorCutQuantizer::COMPONENT_GREEN;
        } else {
            return ColorCutQuantizer::COMPONENT_BLUE;
        }
    }

    /**
     * Finds the point within this box's lowerIndex and upperIndex index of where to split.
     *
     * This is calculated by finding the longest color dimension, and then sorting the
     * sub-array based on that dimension value in each color. The colors are then iterated over
     * until a color is found with atleast the minpoint of the whole box's dimension midpoint.
     *
     * @return int The index of the colors array to split from
     */
    public function findSplitPoint()
    {
        $longestDimension = $this->getLongestColorDimension();

        // We need to sort the colors in this box based on the longest color dimension.
        // As we cant use a Comparator to define the sort logic, we modify each color so that
        // its most significant is the desired dimension.
        $this->colorCutQuantizer->modifySignificantOctet($longestDimension, $this->lowerIndex, $this->upperIndex);

        // Get array to be sorted
        $colors = array_slice($this->colorCutQuantizer->getColors(), $this->lowerIndex, $this->upperIndex - $this->lowerIndex + 1);

        sort($colors);

        // Now revert all of the colors so that they are packed a RGB again
        $this->colorCutQuantizer->modifySignificantOctet($longestDimension, $this->lowerIndex, $this->upperIndex);

        $dimensionMidPoint = $this->midPoint($longestDimension);

        for ($i = $this->lowerIndex; $i <= $this->upperIndex; $i++) {
            $color = $this->colorCutQuantizer->getColors()[$i];

            switch ($longestDimension) {
                case ColorCutQuantizer::COMPONENT_RED:
                    if (Color::red($color) >= $dimensionMidPoint) {
                        return $i;
                    }
                    break;
                case ColorCutQuantizer::COMPONENT_GREEN:
                    if (Color::green($color) >= $dimensionMidPoint) {
                        return $i;
                    }
                    break;
                case ColorCutQuantizer::COMPONENT_BLUE:
                    if (Color::blue($color) >= $dimensionMidPoint) {
                        return $i;
                    }
                    break;
            }
        }

        return $this->lowerIndex;
    }

    /**
     * Return the average color of this box.
     *
     * @return Swatch
     */
    public function getAverageColor()
    {
        $redSum = 0;
        $greenSum = 0;
        $blueSum = 0;
        $totalPopulation = 0;

        for ($i = $this->lowerIndex; $i <= $this->upperIndex; $i++) {
            $color = $this->colorCutQuantizer->getColors()[$i];
            $colorPopulation = $this->colorCutQuantizer->getColorPopulations()[$color];

            $totalPopulation += $colorPopulation;
            $redSum += $colorPopulation * Color::red($color);
            $greenSum += $colorPopulation * Color::green($color);
            $blueSum += $colorPopulation * Color::blue($color);
        }

        $redAverage = (int) round($redSum / $totalPopulation);
        $greenAverage = (int) round($greenSum / $totalPopulation);
        $blueAverage = (int) round($blueSum / $totalPopulation);

        return new Swatch(Color::rgb($redAverage, $greenAverage, $blueAverage), $totalPopulation);
    }

    /**
     * Return the midpoint of this box in the given {@code $dimension}
     *
     * @param int $dimension
     * @return float
     */
    public function midPoint($dimension)
    {
        switch ($dimension) {
            case ColorCutQuantizer::COMPONENT_BLUE:
                return ($this->minBlue + $this->maxBlue) / 2;
            case ColorCutQuantizer::COMPONENT_GREEN:
                return ($this->minGreen + $this->maxGreen) / 2;
            case ColorCutQuantizer::COMPONENT_RED:
            default:
                return ($this->minRed + $this->maxRed) / 2;
        }
    }
} 