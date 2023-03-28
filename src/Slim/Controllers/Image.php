<?php /** @noinspection PhpUnused */
declare(strict_types=1);

namespace Spaethtech\Slim\Controllers;

use Imagick;
use ImagickException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Factory\StreamFactory;
use SpaethTech\Support\Config\PhpConfig as Config;

/**
 * Class Image
 *
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 * @copyright 2022 Spaeth Technologies Inc.
 */
class Image
{
    protected ContainerInterface $container;
    protected Config $config;

    public string $uri;


    public Imagick $imagick;

    /**
     * @param ContainerInterface $container
     * @param string             $uri
     *
     * @throws ImagickException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(ContainerInterface $container, string $uri)
    {
        $this->container    = $container;
        $this->config       = $this->container->get(Config::class);
        $this->uri          = $uri;
        $this->imagick      = new Imagick($this->location());
    }

    /**
     * @throws ImagickException
     */
    public function isCorrectSize(...$params): bool
    {
        $wMatch = false;
        $hMatch = false;

        if (array_key_exists("width", $params) &&
            is_int($params["width"]) && (
                $params["width"] === 0 ||
                $params["width"] === $this->imagick->getImageWidth()
            ))
            $wMatch = true;

        if (array_key_exists("height", $params) &&
            is_int($params["height"]) && (
                $params["height"] === 0 ||
                $params["height"] === $this->imagick->getImageHeight()
            ))
            $hMatch = true;

        return ($wMatch && $hMatch);
    }

    public function render(Response $response): Response
    {
        return $response
            ->withHeader("Content-Type", $this->mime())
            ->withBody((new StreamFactory())->createStream($this->contents()));
    }

    /**
     * @throws ImagickException
     */
//    public function cachedRender(Response $response, string $method, int $width, int $height): Response
//    {
//        $mime = $this->mime();
//        $data = $this->contents();
//
//        if ($this->cachedExists($method, $width, $height))
//        {
//            $temp = new Imagick($this->cachedLocation($method, $width, $height));
//
//            $mime = $temp->getImageMimeType();
//            $data = $temp->getImageBlob();
//        }
//
//        return $response
//            ->withHeader("Content-Type", $mime)
//            ->withBody((new StreamFactory())->createStream($data));
//    }

    public function renderCached(Response $response, string $method, ...$params): Response
    {
        $mime = $this->mime();
        $data = $this->contents();

        if ($this->cachedExists($method, ...$params))
        {
            $temp = new Imagick($this->cachedLocation($method, ...$params));

            $mime = $temp->getImageMimeType();
            $data = $temp->getImageBlob();
        }

        return $response
            ->withHeader("Content-Type", $mime)
            ->withBody((new StreamFactory())->createStream($data));
    }



    #region PATH

    /**
     * @return string
     *
     * @noinspection SpellCheckingInspection
     */
    public function basepath(): string
    {
        return $this->config->get("assets.path");
    }

    /**
     * @return string
     */
    public function dirname(): string
    {
        return pathinfo($this->uri, PATHINFO_DIRNAME);
    }

    /**
     * @return string
     */
    public function filename(): string
    {
        return pathinfo($this->uri, PATHINFO_FILENAME);
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        return pathinfo($this->uri, PATHINFO_EXTENSION);
    }

    /**
     * @param bool $real
     *
     * @return string|false
     */
    public function location(bool $real = false)
    {
        $path = "{$this->basepath()}/{$this->dirname()}/{$this->filename()}.{$this->extension()}";
        return $real ? realpath($path) : $path;
    }

    /**
     * @return bool
     */
    public function exists(): bool
    {
        return $this->location(TRUE) !== false;
    }

    #endregion

    //public function cachedLocation(string $method, int $width, int $height /*, bool $real = false */): string|FALSE
    public function cachedLocation(string $method, ...$params): string
    {
        $parts = count($params) === 0 ? "" : "-".join("-", array_values($params));

        return "{$this->config->get('assets.cache')}/{$this->dirname()}/{$this->filename()}".
            //"--$method-$width-$height.{$this->extension()}";
            "--$method$parts.{$this->extension()}";
    }

    /**
     * @param string $method
     * @param ...$params
     *
     * @return false|string
     */
    public function realCachedLocation(string $method, ...$params)
    {
        return realpath($this->cachedLocation($method, ...$params));
    }

//    public function cachedExists(string $method, int $width, int $height): bool
//    {
//        return $this->cachedLocation($method, $width, $height, TRUE) !== false;
//    }

    public function cachedExists(string $method, ...$params): bool
    {
        return $this->realCachedLocation($method, ...$params) !== false;
    }

    /**
     * @throws ImagickException
     */
//    public function save(string $method, int $width, int $height, bool $force = FALSE): bool
//    {
//        if (!$this->cachedExists($method, $width, $height) || $force)
//        {
//            $cached = $this->cachedLocation($method, $width, $height);
//
//            if (!file_exists(dirname($cached)))
//                mkdir(dirname($cached), 0777, TRUE);
//
//            return $this->imagick->writeImage($cached);
//        }
//
//
//        return false;
//
//    }

    public function save(string $method, ...$params): bool
    {
        $cached = $this->cachedLocation($method, ...$params);

        if (!file_exists(dirname($cached)))
            mkdir(dirname($cached), 0777, TRUE);

        return $this->imagick->writeImage($cached);
    }



    #region DATA

    /**
     * @return string|false
     */
    public function mime()
    {
        return mime_content_type($this->location());
    }

    /**
     * @return string|false
     */
    public function contents()
    {
        return file_get_contents($this->location());
    }

    /**
     * @return string
     */
    public function encoded(): string
    {
        return base64_encode($this->contents());
    }

    #endregion


}
