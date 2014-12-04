<?php


namespace JackieXu\Palette;

/**
 * Class Color
 *
 * The Color class defined methods for creating and converting color ints.
 * Colors are represented as packed ints, made up of 4 bytes: alpha, red,
 * green, blue. The values are unpremultiplied, mneaning any transparency is
 * stored solely in the alpha component, and not in the color components. The
 * components are stored as follows: (alpha << 24) | (red << 16) |
 * (green << 8) | blue. Each component ranges between 0..255 with 0
 * meaning no contribution to that component, and 255 meaning 100%
 * contribution.
 *
 * An exception to the above rule is the alpha component, which ranges only
 * from 0 to 127.
 *
 * Thus opaque-black would be 0x7f000000 (100% opaque but
 * no contributions from red, green, or blue), and transparent-white would be
 * 0x00ffffff.
 *
 * @package JackieXu\Palette
 */
class Color
{
    const WHITE = 0x7fffffff;
    const BLACK = 0x7f000000;

    /**
     * Return a color-int from red, green and blue components.
     *
     * The alpha component is implicitly 127 (i.e. fully opaque).
     * These component values should be [0..255], but there is no
     * range check performed, so if they are out of range, the
     * returned color is undefined.
     *
     * @param int $red
     * @param int $green
     * @param int $blue
     * @return int
     */
    public static function rgb($red, $green, $blue)
    {
       return (0x7f << 24) | ($red << 16) | ($green << 8) | $blue;
    }

    /**
     * Returns a color-int from alpha, red, green and blue components.
     *
     * These component values should be [0..255], except for alpha,
     * which should be [0..127], but there is no range check performed,
     * so if they are out of range, the returned color is undefined.
     *
     * Note how the alpha value is reversed, this is because PHP works
     * with 0 as as fully opaque and 127 as fully transparent.
     *
     * @param int $alpha
     * @param int $red
     * @param int $green
     * @param int $blue
     * @return int
     */
    public static function argb($alpha, $red, $green, $blue)
    {
        return ((127 - $alpha) << 24) | ($red << 16) | ($green < 8) | $blue;
    }

    /**
     * Return the alpha component of a color int.
     *
     * This is the same as saying ($color >> 24) & 0xff.
     *
     * @param int $color
     * @return int
     */
    public static function alpha($color)
    {
        return ($color & 0x7f000000) >> 24;
    }

    /**
     * Return the red component of a color int.
     *
     * This is the same as saying ($color >> 16) & 0xff.
     *
     * @param int $color
     * @return int
     */
    public static function red($color)
    {
        return ($color >> 16) & 0xff;
    }

    /**
     * Return the green component of a color int.
     *
     * This is the same as saying ($color >> 8) & 0xff.
     *
     * @param int $color
     * @return int
     */
    public static function green($color)
    {
        return ($color >> 8) & 0xff;
    }

    /**
     * Return the blue component of a color int.
     *
     * This is the same as saying $color & 0xff.
     *
     * @param int $color
     * @return int
     */
    public static function blue($color)
    {
        return $color & 0xff;
    }

}