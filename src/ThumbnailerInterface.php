<?php
/**
 * Created: 2016-05-18
 * @author Yauhen Saroka <yauhen.saroka@gmail.com>
 */

namespace ysaroka\autothumb;

interface ThumbnailerInterface
{
    const POS_TOP_LEFT = 10;
    const POS_TOP_RIGHT = 11;
    const POS_TOP_CENTER = 12;
    const POS_MID_LEFT = 13;
    const POS_MID_RIGHT = 14;
    const POS_MID_CENTER = 15;
    const POS_BOTTOM_LEFT = 16;
    const POS_BOTTOM_RIGHT = 17;
    const POS_BOTTOM_CENTER = 18;

    /**
     * Loads an image from a file.
     *
     * @param string $imagePath The path to the image.
     *
     * @return $this
     */
    public function load($imagePath);

    /**
     * Gets the mime type associated with the current resource (if available).
     *
     * @return string The mime type.
     */
    public function getMime();

    /**
     * Gets the width of the current image resource.
     *
     * @return int The width.
     */
    public function getWidth();

    /**
     * Gets the height of the current image resource.
     *
     * @return int The height.
     */
    public function getHeight();

    /**
     * Creates a thumbnail of the current resource. If crop is true, the result will be a perfect
     * fit thumbnail with the given dimensions, cropped by the center. If crop is false, the
     * thumbnail will use the best fit for the dimensions.
     *
     * @param int $width Width of the thumbnail.
     * @param int $height Height of the thumbnail.
     * @param bool $crop When set to true, the thumbnail will be cropped from the center to match
     *                      the given size.
     * @param bool $stretch When set to false, an image smaller than the box area won't be scaled up
     *                      to meet the desired size. Defaults to true
     *
     * @return $this
     */
    public function thumbnail($width, $height, $crop = false, $stretch = true);

    /**
     * Convenient method to place a watermark image on top of the current resource.
     *
     * @param mixed $image            The path to the watermark image file or an Imanee object.
     * @param int $placeConstant      One of the self::POS_* constants.
     * @param int   $transparency     Watermark transparency percentage.
     *
     * @return $this
     */
    public function watermark($image, $placeConstant = self::POS_BOTTOM_RIGHT, $transparency = 0);

    /**
     * Output the current image resource as a string.
     *
     * @param string $format The image format (overwrites the currently defined format).
     *
     * @return string The image data as a string.
     */
    public function output($format = null);
}
