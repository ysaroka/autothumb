<?php
/**
 * Created: 2016-05-17
 * @author Yauhen Saroka <yauhen.saroka@gmail.com>
 */

namespace ysaroka\autothumb;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use SplFileObject;
use ysaroka\autothumb\exception\ConfigurationException;
use ysaroka\autothumb\exception\ProcessException;

class AutoThumb
{
    /**
     * Thumbnailer object, must implement the interface ThumbnailerInterface
     * @var ThumbnailerInterface
     */
    private $_thumbnailer;

    /**
     * Absolute path to document root directory
     * @var string
     */
    private $_documentRoot;

    /**
     * Default image extensions, allowed to processing
     * You can change them in the configuration array, parameter "allowed_extensions"
     * @var array
     */
    private $_allowedExtensions = [
        'jpg',
        'jpeg',
        'gif',
        'png',
    ];

    /**
     * List types of miniatures
     * @var array
     */
    private $_types;

    /**
     * Thumbnails prefix
     * @var string
     */
    private $_thumbnailPrefix = 'autothumb';

    /**
     * AutoThumb constructor.
     * @param array $config Configuration array, example:
     * <code>
     * $config = [
     *     // Set web-server document root directory.
     *     // If you want to specify a different directory - you have to change .htaccess file file as follows:
     *     // RewriteRule /other/images/directory/(.*)-autothumb-([\w\d]+)\.([\w\d]+)$ autothumb.process.php?image=$1.$3&type=$2
     *     'document_root' => dirname(__FILE__) . DIRECTORY_SEPARATOR,
     *
     *     'types' => [
     *         // Type name must contain only words and numbers
     *         'small' => [
     *             'width' => 150,
     *             'height' => 100,
     *             // When set to true, the thumbnail will be cropped from the center to match the given size. Defaults to true.
     *             'crop' => true,
     *             // When set to false, an image smaller than the box area won't be scaled up to meet the desired size. Defaults to false.
     *             'stretch' => false,
     *         ],
     *         'medium' => [
     *             'width' => 300,
     *             'height' => 200,
     *             'crop' => true,
     *             'stretch' => false,
     *             'watermark' => dirname(__FILE__) . DIRECTORY_SEPARATOR . 'path/to/image.jpg',
     *             // Watermark position, available values: (top|mid|bottom)_(left|right|center), defaults to "bottom_right"
     *             'watermark_pos' => 'bottom_right',
     *         ],
     *     ],
     * ];
     * </code>
     */
    public function __construct(array $config)
    {
        $this->setConfig($config);
    }

    /**
     * Set or update class configuration
     * @param array $config Configuration array, @see self::__construct()
     * @return $this
     * @throws ConfigurationException
     */
    public function setConfig(array $config)
    {
        if (!isset($config['thumbnailer'])) {
            $this->_thumbnailer = new Thumbnailer();
        } elseif (class_exists($config['thumbnailer'])) {
            $this->_thumbnailer = new $config['thumbnailer'];
        } else {
            throw new ConfigurationException('Class ' . $config['thumbnailer'] . ' does not exist.');
        }

        if (!($this->_thumbnailer instanceof ThumbnailerInterface)) {
            throw new ConfigurationException('Class ' . get_class($this->_thumbnailer) . ' must implement the interface \ysaroka\autothumb\ThumbnailerInterface.');
        }

        if (!isset($config['document_root'])) {
            throw new ConfigurationException('"document_root" key is required.');
        }

        if (!is_dir(realpath($config['document_root']))) {
            throw new ConfigurationException('"document_root": directory "' . $config['document_root'] . '" does not exist.');
        }

        if (!isset($config['types']) || count($config['types']) == 0) {
            throw new ConfigurationException('"types" key is required and should not be empty.');
        }

        foreach ($config['types'] as $type => $params) {
            if (!preg_match('#^[\w\d]+$#', $type)) {
                throw new ConfigurationException('"type": type name "' . $type . '" must contain only words and numbers.');
            }

            if (!isset($params['width'])) {
                $config['types'][$type]['width'] = 100;
            }

            if (!isset($params['height'])) {
                $config['types'][$type]['height'] = 100;
            }

            if (!isset($params['crop'])) {
                $config['types'][$type]['crop'] = true;
            }

            if (!isset($params['stretch'])) {
                $config['types'][$type]['stretch'] = false;
            }

            if (!isset($params['watermark'])) {
                $config['types'][$type]['watermark'] = false;
            } elseif (file_exists($params['watermark']) && !is_dir($params['watermark'])) {
                $config['types'][$type]['watermark'] = realpath($params['watermark']);
                if (isset($params['watermark_pos']) && defined('\ysaroka\autothumb\ThumbnailerInterface::POS_' . strtoupper($params['watermark_pos']))) {
                    $config['types'][$type]['watermark_pos'] = constant('\ysaroka\autothumb\ThumbnailerInterface::POS_' . strtoupper($params['watermark_pos']));
                } else {
                    $config['types'][$type]['watermark_pos'] = ThumbnailerInterface::POS_BOTTOM_RIGHT;
                }
            } else {
                throw new ConfigurationException('Image file "watermark": "' . $params['watermark'] . '" for type "' . $type . '" does not exist.');
            }
        }

        if (isset($config['allowed_extensions']) && is_array($config['allowed_extensions']) && count($config['allowed_extensions']) > 0) {
            $this->_allowedExtensions = $config['allowed_extensions'];
        }

        $this->_documentRoot = realpath($config['document_root']);
        $this->_types = $config['types'];

        return $this;
    }

    /**
     * Get thumbnailer object
     * @return Thumbnailer
     */
    public function getThumbnailer()
    {
        return $this->_thumbnailer;
    }

    /**
     * Get absolute path to document root directory
     * @return string
     */
    public function getDocumentRoot()
    {
        return $this->_documentRoot;
    }

    /**
     * Get image extensions, allowed to processing
     * @return array
     */
    public function getAllowedExtensions()
    {
        return $this->_allowedExtensions;
    }

    /**
     * Get list types of miniatures
     * @return array
     */
    public function getTypes()
    {
        return $this->_types;
    }

    /**
     * Get thumbnail prefix
     * @return string
     */
    public function getThumbnailPrefix()
    {
        return $this->_thumbnailPrefix;
    }

    /**
     * Process of generating thumbnails
     * @param array $variables Variables for processing, 'image' and 'type', required
     * @param bool $output Output thumbnail data
     * @param bool $saveThumbnailFile Save thumbnail image file
     * @return string The image data as a string.
     * @throws ProcessException
     */
    public function process($variables, $output = true, $saveThumbnailFile = true)
    {
        if (!isset ($variables['image'])) {
            throw new ProcessException('Failed to get variable "image".');
        }
        if (!isset($variables['type'])) {
            throw new ProcessException('Failed to get variable "type".');
        }

        // Check path to image
        $splImageFile = new SplFileObject($this->getDocumentRoot() . DIRECTORY_SEPARATOR . $variables['image']);

        if (strpos($splImageFile->getRealPath(), $this->getDocumentRoot()) !== 0) {
            throw new ProcessException('The attempt to go beyond the root directory "' . $this->getDocumentRoot() . '".');
        }
        if (!$splImageFile->isFile()) {
            throw new ProcessException('Original image is not a regular file.');
        }
        if (!in_array($splImageFile->getExtension(), $this->getAllowedExtensions())) {
            throw new ProcessException('Image file extension "' . $splImageFile->getExtension() . '" not allowed for processing.');
        }

        // Get thumbnail type and params
        $type = $variables['type'];
        if (!preg_match('#^[\w\d]+$#', $type)) {
            throw new ProcessException('"type" variable must contain only words and numbers.');
        }
        $types = $this->getTypes();
        if (!isset($types[$type])) {
            throw new ProcessException('Thumbnail type "' . $type . '" is not available.');
        }
        $typeParams = $types[$type];

        // Get thumbnail path
        $thumbnailImagePath = $this->getDocumentRoot() . DIRECTORY_SEPARATOR . $this->getThumbnail($variables['image'], $type);

        if (file_exists($thumbnailImagePath)) {
            throw new ProcessException('Thumbnail file "' . $thumbnailImagePath . '" already exists.');
        }

        // Generate thumbnail and send to client if need
        $thumbnailer = $this->getThumbnailer();
        $thumbnailer->load($splImageFile->getRealPath());
        $thumbnailer->thumbnail($typeParams['width'], $typeParams['height'], $typeParams['crop'], $typeParams['stretch']);

        if ($typeParams['watermark']) {
            $thumbnailer->watermark($typeParams['watermark'], $typeParams['watermark_pos']);
        }

        $imageData = $thumbnailer->output();

        if ($saveThumbnailFile) {
            if (!file_put_contents($thumbnailImagePath, $imageData, LOCK_EX)) {
                throw new ProcessException('Unable to write file: "' . $thumbnailImagePath . '"');
            }
        }

        if ($output) {
            header("Content-type: " . $thumbnailer->getMime());
            echo $imageData;
            exit();
        }

        return $imageData;
    }

    /**
     * Get thumbnail path from original image path
     * @param string $originalImagePath Path to original image
     * @param string $type Thumbnail type
     * @return string
     */
    public function getThumbnail($originalImagePath, $type)
    {
        if (array_key_exists($type, $this->getTypes())) {
            return preg_replace('#^(.*)\.(' . implode('|', $this->getAllowedExtensions()) . ')$#', '$1-' . $this->getThumbnailPrefix() . '-' . $type . '.$2', $originalImagePath);
        }

        return false;
    }

    /**
     * Clean thumbnails for specified thumbnails directory or image
     * Directory or image must exist
     * @param string $path Relative path to thumbnails directory or image
     * @return bool
     */
    public function cleanThumbnails($path = '')
    {
        $cleanPathInfo = new SplFileInfo(realpath($this->getDocumentRoot() . DIRECTORY_SEPARATOR . $path));

        if (strpos($cleanPathInfo->getRealPath(), $this->getDocumentRoot()) === 0) {
            if ($cleanPathInfo->isDir()) {
                // Clean thumbnails in specified directory
                $dirIterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cleanPathInfo->getRealPath()), RecursiveIteratorIterator::SELF_FIRST);

                foreach ($dirIterator as $path => $splFileObject) {
                    /* @var SplFileObject $splFileObject */
                    if (!in_array($splFileObject->getBasename(), ['.', '..'])
                        && preg_match('#.*-' . $this->getThumbnailPrefix() . '-([\w\d]+)\.(' . implode('|', $this->getAllowedExtensions()) . ')$#', $splFileObject->getBasename())
                    ) {
                        unlink($splFileObject->getRealPath());
                    }
                }

                return true;

            } elseif ($cleanPathInfo->isFile() && in_array($cleanPathInfo->getExtension(), $this->getAllowedExtensions())) {
                // Clean thumbnails for one image
                $imagePathRemExt = $cleanPathInfo->getPath() . DIRECTORY_SEPARATOR . $cleanPathInfo->getBasename('.' . $cleanPathInfo->getExtension());

                $thumbnails = glob($imagePathRemExt . '-' . $this->getThumbnailPrefix() . '-*');
                foreach ($thumbnails as $thumbnailPath) {
                    unlink($thumbnailPath);
                }

                return true;
            }
        }

        return false;
    }
}