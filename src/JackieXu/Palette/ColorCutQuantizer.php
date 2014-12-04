<?php


namespace JackieXu\Palette;

/**
 * Class ColorCutQuantizer
 *
 * A color quantizer based on the median-cut algorithm, but optimized for picking out distinct
 * colors rather than representation colors.
 *
 * The color space is represented as a 3-dimensional cube with each dimension being an RGB
 * component. The cube is then repeatedly divided until we have reduced the color space to the
 * requested number of colors. An average is then generated from eacch cube.
 *
 * What makes this different to median-cut is that median-cut divided cubes so that all of the cubes
 * have roughly the same population, where this quantizer divides boxes based on their color volume.
 * This means that the color space is divided into distinct colors, rather than representative
 * colors.
 *
 * @package JackieXu\Palette
 */
class ColorCutQuantizer
{
    const BLACK_MAX_LIGHTNESS = 0.05;
    const WHITE_MIN_LIGHTNESS = 0.95;

    const COMPONENT_RED = -3;
    const COMPONENT_GREEN = -2;
    const COMPONENT_BLUE = -1;

    /**
     * @var array
     */
    protected $colors;

    /**
     * @var array
     */
    protected $colorPopulations;

    protected $quantizedColors;

    /**
     * ColorCutQuantizer constructor
     *
     * @param ColorHistogram $colorHistogram
     * @param int $maxColors
     */
    public function __construct(ColorHistogram $colorHistogram, $maxColors)
    {
//        $rawColorCount = $colorHistogram->getNumberOfColors();
        $rawColors = $colorHistogram->getColors();
        $rawColorCounts = $colorHistogram->getColorCounts();

        // Create dictionary to retrieve color color population without index
        $this->colorPopulations = array();
        for ($i = 0; $i < count($rawColors); $i++) {
            $this->colorPopulations[$rawColors[$i]] = $rawColorCounts[$i];
        }

        // Now go through all of the colors and keep those which we do not want to ignore
        $this->colors = array();
        $validColorCount = 0;
        foreach ($rawColors as $color) {
            if (!$this->shouldIgnoreColor($color)) {
                $this->colors[] = $color;
                $validColorCount++;
            }
        }

        // Check if image has fewer color than the maximum requested, and thus return
        // else quantize to reduce the number of colors
        if ($validColorCount <= $maxColors) {
            $this->quantizedColors = array();
            foreach ($this->colors as $color) {
                $this->quantizedColors[] = new Swatch($color, $this->colorPopulations[$color]);
            }
        } else {
            $this->quantizedColors = $this->quantizePixels($validColorCount - 1, $maxColors);
        }
    }

    /**
     * Return the list of quantized colors
     *
     * @return array
     */
    public function getQuantizedColors()
    {
        return $this->quantizedColors;
    }

    /**
     * Get colors
     *
     * @return int[]
     */
    public function getColors()
    {
        return $this->colors;
    }

    /**
     * Get color pupulations
     *
     * @return array
     */
    public function getColorPopulations()
    {
        return $this->colorPopulations;
    }

    /**
     * Quantize pixels to reduce number of colors
     *
     * @param int $maxColorIndex
     * @param int $maxColors
     * @return array
     */
    private function quantizePixels($maxColorIndex, $maxColors)
    {
        // Creata a priority queue which is sorted by volume descending. This means we always
        // split the largest box in the queue
        $pq = array();

        // To start offer a box which contains all of the colors
        $pq[] = new VBox($this, 0, $maxColorIndex);

        // Now go through the boxes, splitting them until we have reached maxColors or there are no
        // more boxes to split
        $pq = $this->splitBoxes($pq, $maxColors);

        // Finally return the average colors of the color boxes
        return $this->generateAverageColors($pq);
    }

    /**
     * Iterate through the queue, popping {@link Vbox} objects from the
     * queue and splitting them. Once split, the new box and the remaining
     * box are offered back to the queue.
     *
     * @param VBox[] $queue
     * @param $maxSize
     * @return array
     */
    private function splitBoxes(array $queue, $maxSize)
    {
        while (count($queue) < $maxSize) {
            $vbox = array_shift($queue);

            if (!is_null($vbox) && $vbox->canSplit()) {
                $queue[] = $vbox->splitBox();
                $queue[] = $vbox;

                usort($queue, array('JackieXu\Palette\ColorCutQuantizer', 'compareVboxes'));
            } else {
                return $queue;
            }
        }

        return $queue;
    }

    /**
     * Generate average colors
     *
     * @param Vbox[] $vboxes
     * @return array
     */
    private function generateAverageColors(array $vboxes)
    {
        $colors = array();

        foreach ($vboxes as $vbox) {
            $color = $vbox->getAverageColor();

            // As we're averaging a color box, we can still get colors which we do not want, so
            // we check again here
            if (!$this->shouldIgnoreColor($color)) {
                $colors[] = $color;
            }
        }

        return $colors;
    }

    /**
     * @param int|Swatch $color
     * @return bool
     */
    private function shouldIgnoreColor($color)
    {
        // Check for swatch, else use color int
        if ($color instanceof Swatch) {
            $hsl = $color->getHsl();
        } else {
            $hsl = ColorUtils::convertRGBToHSL(Color::red($color), Color::green($color), Color::blue($color));
        }

        return $this->isWhite($hsl) || $this->isBlack($hsl) || $this->isNearRedILine($hsl);

    }

    /**
     * Check whether color is mostly black
     *
     * @param float[] $hsl
     * @return bool
     */
    private function isBlack($hsl)
    {
        return $hsl[2] <= self::BLACK_MAX_LIGHTNESS;
    }

    /**
     * Check whether color is mostly white
     *
     * @param float[] $hsl
     * @return bool
     */
    private function isWhite($hsl)
    {
        return $hsl[2] >= self::WHITE_MIN_LIGHTNESS;
    }

    /**
     * Check whether color lies near the red I line
     * @param float[] $hsl
     * @return bool
     */
    private function isNearRedILine($hsl)
    {
        return $hsl[0] >= 10 && $hsl[0] <= 37 && $hsl[1] <= 0.82;
    }

    /**
     * Modify the significant octet in a packed color int. Allow sorting based on the value of a
     * single color component.
     *
     * @param int $dimension
     * @param int $lowerIndex
     * @param int $upperIndex
     * @see VBox#findSplitPoint
     */
    public function modifySignificantOctet($dimension, $lowerIndex, $upperIndex)
    {
        switch ($dimension) {
            // Already in RGB, no need to do anything
            case ColorCutQuantizer::COMPONENT_RED:
                break;
            // We need to do an RGB to GRB swap, or vice-versa
            case ColorCutQuantizer::COMPONENT_GREEN:
                for ($i = $lowerIndex; $i <= $upperIndex; $i++) {
                    $color = $this->colors[$i];
                    $this->colors[$i] = Color::rgb(($color >> 8) & 0xff, ($color >> 16) & 0xff, $color & 0xff);
                }
                break;
            // We need to do an RGB to BGR swap, or vice-versa
            case ColorCutQuantizer::COMPONENT_BLUE:
                for ($i = $lowerIndex; $i <= $upperIndex; $i++) {
                    $color = $this->colors[$i];
                    $this->colors[$i] = Color::rgb($color & 0xff, ($color >> 8) & 0xff, ($color >> 16) & 0xff);
                }
                break;
        }
    }

    /**
     * Compare two vboxes two order them
     *
     * @param Vbox $leftBox
     * @param Vbox $rightBox
     * @return int
     */
    private static function compareVboxes(Vbox $leftBox, Vbox $rightBox) {
        return $rightBox->getVolume() - $leftBox->getVolume();
    }
} 