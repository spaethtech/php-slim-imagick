<?php /** @noinspection PhpUnused, DuplicatedCode */
declare(strict_types=1);

namespace SpaethTech\Slim\Controllers\Imaging\Resizing;

use Image;
use ImagickException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use SpaethTech\Slim\Controllers\Images\Rendering;

/**
 * Dimension
 *
 * @author    Ryan Spaeth <rspaeth@spaethtech.com>
 * @copyright 2022, Spaeth Technologies Inc.
 */
trait Crop
{

    /**
     * @param Request  $request
     * @param Response $response
     *
     * @return Response
     * @throws HttpNotFoundException
     * @throws ImagickException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @noinspection HtmlUnknownTag
     */
    public function scale(Request $request, Response $response): Response
    {
        return $this->render($request, $response, function(Image $image, $width, $height)
        {
            $image->imagick->scaleImage($width, $height);

        });

    }
}
