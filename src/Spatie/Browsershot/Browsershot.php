<?php

namespace Spatie\Browsershot;

use Exception;
use Intervention\Image\ImageManager;

/**
 * Class Browsershot.
 */
class Browsershot
{

    /**
     * @var int
     */
    private $width;

    /**
     * @var int
     */
    protected $height;

    /**
     * @var int
     */
    protected $quality;

    /**
     * @var int
     */
    protected $url;

    /**
     * @var string
     */
    protected $binPath;

    /**
     * @var array
     */
    protected $phantomJSOptions = [
        "--ssl-protocol=any",
        "--ignore-ssl-errors=true"
    ];

    /**
     * @var array
     */
    protected $paperSize = [
        "format"      => "A4",
        "orientation" => "portrait",
        "margin"      => [
            "left"   => "1cm",
            "right"  => "1cm",
            "top"    => "1cm",
            "bottom" => "1cm",
        ]
    ];

    /**
     * @var int
     */
    protected $timeout;


    public function __construct($binPath = '', $width = 640, $height = 480, $quality = 60, $timeout = 5000)
    {
        if ($binPath == '') {
            $binPath = realpath(dirname(__FILE__) . '/../../../bin/phantomjs');
        }

        $this->binPath = $binPath;
        $this->width   = $width;
        $this->height  = $height;
        $this->quality = $quality;
        $this->timeout = $timeout;

        return $this;
    }


    /**
     * @param string $binPath
     *
     * @return $this
     */
    public function setBinPath($binPath)
    {
        $this->binPath = $binPath;

        return $this;
    }


    /**
     * @param int $width
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setWidth($width)
    {
        if ( ! is_numeric($width)) {
            throw new Exception('Width must be numeric');
        }

        $this->width = $width;

        return $this;
    }


    /**
     * @param int $height
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setHeight($height)
    {
        if ( ! is_numeric($height)) {
            throw new Exception('Height must be numeric');
        }

        $this->height = $height;

        return $this;
    }


    /**
     * Set the image quality.
     *
     * @param $quality
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setQuality($quality)
    {
        if ( ! is_numeric($quality) || $quality < 1 || $quality > 100) {
            throw new Exception('Quality must be a numeric value between 1 - 100');
        }

        $this->quality = $quality;

        return $this;
    }


    /**
     * Set to height so the whole page will be rendered.
     *
     * @return $this
     */
    public function setHeightToRenderWholePage()
    {
        $this->height = 0;

        return $this;
    }


    /**
     * @param string $url
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setUrl($url)
    {
        if ( ! strlen($url) > 0) {
            throw new Exception('No url specified');
        }

        $this->url = $url;

        return $this;
    }


    /**
     * @param int $timeout
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setTimeout($timeout)
    {
        if ( ! is_numeric($timeout)) {
            throw new Exception('Height must be numeric');
        }

        $this->timeout = $timeout;

        return $this;
    }


    /**
     * @param array $options
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setPhantomJSOptions($options)
    {
        if ( ! is_array($options)) {
            throw new Exception('Options must be an array');
        }

        $this->phantomJSOptions = $options;

        return $this;
    }


    /**
     * @param $size
     *
     * @return $this
     *
     * @throws Exception
     */
    public function setPaperSize($size)
    {
        if ( ! is_array($size)) {
            throw new Exception('Options must be an array');
        }

        $this->paperSize = $size;

        return $this;
    }


    /**
     * Convert the webpage to an image.
     *
     * @param string $targetFile The path of the file where the screenshot should be saved
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function save($targetFile)
    {
        if ($targetFile == '') {
            throw new Exception('targetfile not set');
        }

        if ( ! in_array(strtolower(pathinfo($targetFile, PATHINFO_EXTENSION)), [ 'jpeg', 'jpg', 'png', 'pdf' ])) {
            throw new Exception('targetfile extension not valid');
        }

        if ($this->url == '') {
            throw new Exception('url not set');
        }

        if (filter_var($this->url, FILTER_VALIDATE_URL) === false) {
            throw new Exception('url is invalid');
        }

        if ( ! file_exists($this->binPath)) {
            throw new Exception('binary does not exist');
        }

        $pdf = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION)) == 'pdf' ;
        $this->takeScreenShot($targetFile, $pdf);

        if ( ! file_exists($targetFile) or filesize($targetFile) < 1024) {
            throw new Exception('could not create screenshot');
        }

        if ( ! $pdf && $this->height > 0) {
            $imageManager = new ImageManager();
            $imageManager->make($targetFile)
                         ->crop($this->width, $this->height, 0, 0)
                         ->save($targetFile, $this->quality);
        }

        return true;
    }


    /**
     * Take the screenshot.
     *
     * @param $targetFile
     * @param $pdf
     */
    protected function takeScreenShot($targetFile, $pdf = false)
    {
        $tempJsFileHandle = tmpfile();
        fwrite($tempJsFileHandle, $this->getPhantomJsScript($targetFile, $pdf));
        shell_exec($this->getShellCmd(stream_get_meta_data($tempJsFileHandle)['uri']));
        fclose($tempJsFileHandle);
    }


    /**
     * Generate Shell command line
     * @param $tempFileName
     * @return string
     */
    protected function getShellCmd($tempFileName)
    {
        return escapeshellcmd("{$this->binPath} {$this->getPhantomJSOptions()} {$tempFileName}");
    }


    /**
     * Get PhantomJS Options
     * @return string
     */
    protected function getPhantomJSOptions()
    {
        return implode(" ", $this->phantomJSOptions);
    }


    /**
     * Get the script to be executed by phantomjs.
     *
     * @param string  $targetFile
     * @param boolean $pdf
     *
     * @return string
     */
    protected function getPhantomJsScript($targetFile, $pdf = false)
    {
        return "
            var page = require('webpage').create();
            " . ($pdf ? $this->getPaperSizeScript() : '') . "
            page.settings.javascriptEnabled = true;
            " . ( ! $pdf ? $this->getViewPortScript() : '') . "
            page.open('{$this->url}', function() {
               window.setTimeout(function(){
                page.render('{$targetFile}');
                phantom.exit();
            }, {$this->timeout});
        });";
    }


    /**
     * @return string
     */
    protected function getPaperSizeScript()
    {
        return "page.paperSize = ".json_encode($this->paperSize).";" ;
    }


    /**
     * @return string
     */
    protected function getViewPortScript()
    {
        return "page.viewportSize = { width: " . $this->width . ( $this->height == 0 ? '' : ', height: ' . $this->height ) . " };" ;
    }
}
