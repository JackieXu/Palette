<?php


namespace JackieXu\Palette;

/**
 * Class ColorHistogram
 *
 * Provides a histrogram for RGB values.
 *
 * @package JackieXu\Palette
 */
class ColorHistogram
{
    protected $colors;
    protected $colorCounts;
    protected $numberOfColours;

    /**
     * @param $imagePath
     * @throws \Exception
     */
    public function __construct($imagePath)
    {
        $imageType = exif_imagetype($imagePath);

        switch ($imageType) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($imagePath);
                break;
            default:
                throw new \Exception("Image type not supported.");
        }

        // Get image dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        $pixels = array();

        // Loop through pixels
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $pixels[] = imagecolorat($image, $x, $y);
            }
        }

        // Sort pixels
        sort($pixels);

        // Count number of colors
        $this->numberOfColours = $this->countDistinctColors($pixels);

        // Create arrays
        $this->colors = array();
        $this->colorCounts = array();

        // Count frequencies of each color
        $this->countFrequencies($pixels);
    }

    /**
     * Returns number of distinct colors in the image
     *
     * @return int
     */
    public function getNumberOfColors()
    {
        return $this->numberOfColours;
    }

    /**
     * Return array containing all distinct colors in the image
     *
     * @return int[]
     */
    public function getColors()
    {
        return $this->colors;
    }

    /**
     * Return array containing the frequencies of distinct colors within the image
     *
     * @return int[]
     */
    public function getColorCounts()
    {
        return $this->colorCounts;
    }

    private function countDistinctColors($pixels)
    {
        // If we have less than 2 pixels, we can stop here
        if (count($pixels) < 2) {
            return count($pixels);
        }

        // If we have at least 2 pixels, we have a minimum of 1 color
        $colorCount = 1;
        $currentColor = $pixels[0];

        // Iterate from the second pixel to the end, counting distinct colors
        for ($i = 1; $i < count($pixels); $i++) {
            if ($pixels[$i] !== $currentColor) {
                $currentColor = $pixels[$i];
                $colorCount++;
            }
        }

        return $colorCount;
    }

    /**
     * Count color frequencies
     *
     * @param $pixels
     */
    private function countFrequencies($pixels)
    {
        // No pixels to count
        if (count($pixels) === 0) {
            return;
        }

        $currentColorIndex = 0;
        $currentColor = $pixels[0];

        $this->colors[$currentColorIndex] = $currentColor;
        $this->colorCounts[$currentColorIndex] = 1;

        // If we only have one pixel, we can stop here
        if (count($pixels) === 1) {
            return;
        }

        // Now iterate from the second pixel to the end, population distinct colors
        for ($i = 1; $i < count($pixels); $i++) {
            if ($pixels[$i] === $currentColor) {
                $this->colorCounts[$currentColorIndex]++;
            } else {
                $currentColor = $pixels[$i];

                $currentColorIndex++;
                $this->colors[$currentColorIndex] = $currentColor;
                $this->colorCounts[$currentColorIndex] = 1;
            }
        }
    }
}