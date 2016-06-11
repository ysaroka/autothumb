<?php
/**
 * Created: 2016-06-11
 * @author Yauhen Saroka <yauhen.saroka@gmail.com>
 */

namespace ysaroka\autothumb\tests;

use ysaroka\autothumb\AutoThumb;

class AutoThumbTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \ysaroka\autothumb\AutoThumb $autoThumb
     */
    protected $autoThumb;

    /**
     * @var array
     */
    protected $config;

    public function setup()
    {
        $this->config = require(TEST_DIR . DIRECTORY_SEPARATOR . 'data/autothumb.config.php');
        $this->autoThumb = new AutoThumb($this->config);
    }

    public function tearDown()
    {
        $this->autoThumb = null;
    }

    public function testGetters()
    {
        $this->config['allowed_extensions'] = ['jpg', 'png'];
        $this->autoThumb->setConfig($this->config);

        $this->assertEquals($this->config['allowed_extensions'], $this->autoThumb->getAllowedExtensions());
        $this->assertInstanceOf('\ysaroka\autothumb\Thumbnailer', $this->autoThumb->getThumbnailer());
        $this->assertEquals(realpath($this->config['document_root']), $this->autoThumb->getDocumentRoot());
        $this->assertTrue(array_key_exists('small', $this->autoThumb->getTypes()) &&
                          array_key_exists('medium', $this->autoThumb->getTypes()) &&
                          array_key_exists('large', $this->autoThumb->getTypes()) &&
                          array_key_exists('50x50', $this->autoThumb->getTypes()) &&
                          array_key_exists('100x200', $this->autoThumb->getTypes()) &&
                          array_key_exists('empty', $this->autoThumb->getTypes()),
            'Wrong thumbnail types after setConfig()'
        );
    }

    public function testGetThumbnail()
    {
        $this->assertEquals('/web/images/test-image-autothumb-small.jpg', $this->autoThumb->getThumbnail('/web/images/test-image.jpg', 'small'),
            'Incorrect thumbnail path generation.');
    }

    public function testSetConfigCorrectThumbnailer()
    {
        $this->config['thumbnailer'] = '\ysaroka\autothumb\Thumbnailer';
        $this->autoThumb->setConfig($this->config);
        $this->assertInstanceOf('\ysaroka\autothumb\Thumbnailer', $this->autoThumb->getThumbnailer());
    }

    public function testTypesDefaultModification()
    {
        $modifiedTypes = $this->autoThumb->getTypes();
        $this->assertTrue(isset($modifiedTypes['empty']['width']) && $modifiedTypes['empty']['width'] == 100);
        $this->assertTrue(isset($modifiedTypes['empty']['height']) && $modifiedTypes['empty']['height'] == 100);
        $this->assertTrue(isset($modifiedTypes['empty']['crop']) && $modifiedTypes['empty']['crop'] === true);
        $this->assertTrue(isset($modifiedTypes['empty']['stretch']) && $modifiedTypes['empty']['stretch'] === false);
        $this->assertTrue(isset($modifiedTypes['empty']['watermark']) && $modifiedTypes['empty']['watermark'] === false);
    }

    /**
     * @expectedException        \ysaroka\autothumb\exception\ConfigurationException
     * @expectedExceptionMessage Class \NotAnExistingClass\NotAnExistingClass does not exist.
     */
    public function testSetConfigExceptionNotAnExistingThumbnailer()
    {
        $this->config['thumbnailer'] = '\NotAnExistingClass\NotAnExistingClass';
        $this->autoThumb->setConfig($this->config);
    }

    /**
     * @expectedException        \ysaroka\autothumb\exception\ConfigurationException
     * @expectedExceptionMessage Class ysaroka\autothumb\tests\data\BadThumbnailer must implement the interface \ysaroka\autothumb\ThumbnailerInterface.
     */
    public function testSetConfigExceptionBadThumbnailerInterface()
    {
        $this->config['thumbnailer'] = '\ysaroka\autothumb\tests\data\BadThumbnailer';
        $this->autoThumb->setConfig($this->config);
    }

    /**
     * @expectedException        \ysaroka\autothumb\exception\ConfigurationException
     * @expectedExceptionMessage "document_root" key is required.
     */
    public function testSetConfigExceptionEmptyDocumentRoot()
    {
        unset($this->config['document_root']);
        $this->autoThumb->setConfig($this->config);
    }

    /**
     * @expectedException        \ysaroka\autothumb\exception\ConfigurationException
     * @expectedExceptionMessageRegExp /directory .* does not exist/i
     */
    public function testSetConfigExceptionInvalidDocumentRoot()
    {
        $this->config['document_root'] = TEST_DIR . 'invalid/path/';
        $this->autoThumb->setConfig($this->config);
    }

    /**
     * @expectedException        \ysaroka\autothumb\exception\ConfigurationException
     * @expectedExceptionMessage "types" key is required and should not be empty.
     */
    public function testSetConfigExceptionEmptyTypes()
    {
        unset($this->config['types']);
        $this->autoThumb->setConfig($this->config);
    }

    /**
     * @expectedException        \ysaroka\autothumb\exception\ConfigurationException
     * @expectedExceptionMessage "type": type name "sma~ll" must contain only words and numbers.
     */
    public function testSetConfigExceptionInvalidType()
    {
        $this->config['types'] = [
            'sma~ll' => [],
        ];
        $this->autoThumb->setConfig($this->config);
    }

    /**
     * @expectedException        \ysaroka\autothumb\exception\ConfigurationException
     * @expectedExceptionMessageRegExp /Image file "watermark": .* for type "small" does not exist/i
     */
    public function testSetConfigExceptionInvalidWatemarkPath()
    {
        $this->config['types'] = [
            'small' => [
                'watermark' => TEST_DIR . 'data/images/watermarks/invalid/path/watermark-invalid.png',
            ],
        ];
        $this->autoThumb->setConfig($this->config);
    }
}