<?php /** @noinspection PhpUnused */
declare(strict_types=1);

namespace SpaethTech\Slim\Resources;

use Imagick;
use ImagickException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Factory\StreamFactory;
use SpaethTech\Slim\Resources\Modifiers\Dimension;

/**
 * Class Image
 *
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 * @copyright 2022 Spaeth Technologies Inc.
 */
class ImageResource extends Resource implements Cacheable
{
    use Dimension;

    protected ?Imagick $imagick = null;

    #region Getters

    /**
     * @throws ImagickException
     */
    public function getImagick(): Imagick
    {
        if ($this->imagick === null)
            $this->imagick = new Imagick($this->getLocation());

        return $this->imagick;
    }

    /**
     * @return string The filename suffix to use when caching this Resource.
     */
    protected function getCachedSuffix(): string
    {
        if (is_null($this->params) || count($this->params) === 0)
            return "";

        $suffix = "";

        foreach($this->params as $key => $value)
        {
            if (!method_exists($this, $method = "{$key}Suffix"))
                continue;

            $suffix .= $this->$method($value);
        }

        return $suffix;

    }

    #endregion

    #region Methods

    /**
     * @param Response $response This Resource's Response before rendering.
     * @param bool $useCached TRUE to allow cached versions of this Resource.
     *
     * @return Response This Resource's Response after rendering.
     *
     * @throws ImagickException
     */
    public function render(Response $response, bool $useCached = FALSE): Response
    {
        $mime = $this->getMime();
        $data = $this->getContents();

        if ($useCached && $this->cached())
        {
            $temp = new Imagick($this->getCachedLocation());
            $mime = $temp->getImageMimeType();
            $data = $temp->getImageBlob();
        }

        return $response
            ->withHeader("Content-Type", $mime)
            ->withBody((new StreamFactory())->createStream($data));
    }

    /**
     * @inheritDoc
     * @throws ImagickException
     */
    public function cache(): bool
    {
        $cached = $this->getCachedLocation();

        if (!file_exists(dirname($cached)))
            mkdir(dirname($cached), 0777, TRUE);

        return $this->getImagick()->writeImage($cached);
    }

    #endregion

}
