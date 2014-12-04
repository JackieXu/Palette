<?php


namespace JackieXu\Palette;

/**
 * Class Swatch
 *
 * @package JackieXu\Palette
 */
class Swatch
{
    /**
     * @var int
     */
    protected $red;

    /**
     * @var int
     */
    protected $green;

    /**
     * @var int
     */
    protected $blue;

    /**
     * @var int
     */
    protected $rgb;

    /**
     * @var int
     */
    protected $population;

    /**
     * @var bool
     */
    protected $generatedTextColors;

    /**
     * @var int
     */
    protected $titleTextColor;

    /**
     * @var int
     */
    protected $bodyTextColor;

    /**
     * @var float[]
     */
    protected $hsl;

    /**
     * @param integer $color
     * @param integer $population
     */
    public function __construct($color, $population)
    {
        $this->red = Color::red($color);
        $this->green = Color::green($color);
        $this->blue = Color::blue($color);
        $this->rgb = $color;
        $this->population = $population;
    }

    /**
     * Get RGB color-int
     *
     * @return integer
     */
    public function getRgb()
    {
        return $this->rgb;
    }

    /**
     * Get HSL values
     *
     * @return float[]
     */
    public function getHsl()
    {
        if (is_null($this->hsl)) {
            $this->hsl = ColorUtils::convertRGBToHSL($this->red, $this->green, $this->blue);
        }
        return $this->hsl;
    }

    /**
     * Get color population
     *
     * @return int
     */
    public function getPopulation()
    {
        return $this->population;
    }

    /**
     * Get title text color
     *
     * @return int
     */
    public function getTitleTextColor()
    {
        $this->ensureTextColorsGenerated();
        return $this->titleTextColor;
    }

    /**
     * Get body text color
     *
     * @return mixed
     */
    public function getBodyTextColor()
    {
        $this->ensureTextColorsGenerated();
        return $this->bodyTextColor;
    }

    /**
     * Make sure the text colors are generated
     */
    private function ensureTextColorsGenerated()
    {
        if (is_null($this->generatedTextColors)) {
            $this->titleTextColor = ColorUtils::getTextColorForBackground($this->rgb,Palette::MIN_CONTRAST_TITLE_TEXT);
            $this->bodyTextColor = ColorUtils::getTextColorForBackground($this->rgb, Palette::MIN_CONTRAST_BODY_TEXT);
            $this->generatedTextColors = true;
        }
    }
} 