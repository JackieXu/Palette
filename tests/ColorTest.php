<?php

use JackieXu\Palette\ColorHistogram;
use JackieXu\Palette\ColorCutQuantizer;
use JackieXu\Palette\Palette;

class ColorTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var ColorHistogram
     */
    protected $histogram;

    /**
     * @var ColorCutQuantizer
     */
    protected $quantizer;

    /**
     * @var Palette
     */
    protected $palette;

    public function setUp()
    {
        $this->histogram = new ColorHistogram(__DIR__.'/images/sunset.jpg');
        $this->quantizer = new ColorCutQuantizer($this->histogram, 16);
        $this->palette = new Palette($this->quantizer->getQuantizedColors());
    }

    public function tearDown()
    {
        $this->palette = null;
        $this->quantizer = null;
        $this->histogram = null;
    }

    public function testIsObject()
    {
        $this->assertTrue(is_object($this->histogram));
        $this->assertTrue(is_array($this->histogram->getColors()));
        $this->assertTrue(is_array($this->histogram->getColorCounts()));
        $this->assertTrue(is_int($this->histogram->getNumberOfColors()));
        $this->assertTrue(is_object($this->quantizer));
        $this->assertTrue(is_object($this->palette));
    }

    public function testColors()
    {
        $this->assertTrue(is_object($this->palette->getVibrantSwatch()), "Object test failed");
        $this->assertTrue(!is_null($this->palette->getVibrantSwatch()), "Null test failed");
        $a = $this->palette->getDarkVibrantSwatch()->getRgb();
        var_dump(array(
            \JackieXu\Palette\Color::red($a),
            \JackieXu\Palette\Color::green($a),
            \JackieXu\Palette\Color::blue($a),
            \JackieXu\Palette\Color::alpha($a)
        ));
        die();
    }
} 