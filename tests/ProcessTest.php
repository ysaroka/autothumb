<?php
/**
 * Created: 2016-06-11
 * @author Yauhen Saroka <yauhen.saroka@gmail.com>
 */

namespace ysaroka\autothumb\tests;

use ysaroka\autothumb\AutoThumb;

class ProcessTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \ysaroka\autothumb\AutoThumb $autoThumb
     */
    protected $autoThumb;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var array
     */
    protected $variables;

    public function setup()
    {
        $this->config = require(TEST_DIR . DIRECTORY_SEPARATOR . 'data/autothumb.config.php');
        $this->autoThumb = new AutoThumb($this->config);
        $this->variables = [
            'image' => 'images/original-image-1024x768.jpg',
            'type' => 'small',
        ];
        $this->autoThumb->cleanThumbnails($this->variables['image']);
    }

    public function tearDown()
    {
        $this->autoThumb = null;
    }

    public function testProcessCreateThumbnail()
    {
        $thumbnailPath = $this->autoThumb->getDocumentRoot() . DIRECTORY_SEPARATOR . $this->autoThumb->getThumbnail($this->variables['image'], $this->variables['type']);
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }

        $this->autoThumb->process($this->variables, false);
        $this->assertTrue(file_exists($thumbnailPath), 'The thumbnail file was not created.');
        $thumbnailInfo = getimagesize($thumbnailPath);
        $this->assertEquals(150, $thumbnailInfo[0], 'Wrong thumbnail width.');
        $this->assertEquals(100, $thumbnailInfo[1], 'Wrong thumbnail height.');
    }

    public function testProcessCreateThumbnailWatermark()
    {
        $this->variables['type'] = 'large';
        $thumbnailPath = $this->autoThumb->getDocumentRoot() . DIRECTORY_SEPARATOR . $this->autoThumb->getThumbnail($this->variables['image'], $this->variables['type']);
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }

        $this->autoThumb->process($this->variables, false);
        $this->assertTrue(file_exists($thumbnailPath), 'The watermark thumbnail file was not created.');
        $thumbnailInfo = getimagesize($thumbnailPath);
        $this->assertEquals(600, $thumbnailInfo[0], 'Wrong watermark thumbnail width.');
        $this->assertEquals(400, $thumbnailInfo[1], 'Wrong watermark thumbnail height.');
    }

    public function testCleanThumbnails()
    {
        $thumbnailPath = $this->autoThumb->getDocumentRoot() . DIRECTORY_SEPARATOR . $this->autoThumb->getThumbnail($this->variables['image'], $this->variables['type']);
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }

        $this->autoThumb->process($this->variables, false);
        $this->autoThumb->cleanThumbnails('images/');
        $this->assertFalse(file_exists($thumbnailPath), 'Clean thumbnails in specified directory does not work.');

        $this->autoThumb->process($this->variables, false);
        $this->autoThumb->cleanThumbnails($this->variables['image']);
        $this->assertFalse(file_exists($thumbnailPath), 'Clean thumbnails for one image does not work.');
    }

    /**
     * @expectedException        \ysaroka\autothumb\exception\ProcessException
     * @expectedExceptionMessage Failed to get variable "image".
     */
    public function testProcessExceptionEmptyImage()
    {
        unset($this->variables['image']);
        $this->autoThumb->process($this->variables, false);
    }

    /**
     * @expectedException        \ysaroka\autothumb\exception\ProcessException
     * @expectedExceptionMessage Failed to get variable "type".
     */
    public function testProcessExceptionEmptyType()
    {
        unset($this->variables['type']);
        $this->autoThumb->process($this->variables, false);
    }

    /**
     * @expectedException        \RuntimeException
     * @expectedExceptionMessageRegExp /no such file or directory/i
     */
    public function testProcessExceptionInvalidImagePath()
    {
        $this->variables['image'] = 'invalid/path/image-invalid.jpg';
        $this->autoThumb->process($this->variables, false);
    }

    /**
     * @expectedException        \ysaroka\autothumb\exception\ProcessException
     * @expectedExceptionMessageRegExp /image file extension "[\w\d]+" not allowed for processing/i
     */
    public function testProcessExceptionAllowedExtensions()
    {
        $this->config['allowed_extensions'] = ['other-ext'];
        $this->autoThumb->setConfig($this->config);

        $this->autoThumb->process($this->variables, false);
    }

    /**
     * @expectedException        \ysaroka\autothumb\exception\ProcessException
     * @expectedExceptionMessageRegExp /thumbnail file ".*" already exists/i
     */
    public function testProcessExceptionThumbnailExists()
    {
        $this->autoThumb->process($this->variables, false);
        $this->autoThumb->process($this->variables, false);
    }

    /**
     * @expectedException        \ysaroka\autothumb\exception\ProcessException
     * @expectedExceptionMessage "type" variable must contain only words and numbers.
     */
    public function testProcessExceptionInvalidTypeName()
    {
        $this->variables['type'] = 'sma~ll';
        $this->autoThumb->process($this->variables, false);
    }

}