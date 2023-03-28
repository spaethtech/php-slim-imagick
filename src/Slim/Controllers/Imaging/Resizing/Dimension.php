<?php /** @noinspection PhpUnused, DuplicatedCode */
declare(strict_types=1);

namespace SpaethTech\Slim\Controllers\Imaging\Resizing;

use Spaethtech\Slim\Controllers\Image;
use ImagickException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Spaethtech\Slim\Controllers\ImageController;

//use SpaethTech\Slim\Controllers\Images\Rendering;

/**
 * Dimension
 *
 * @author    Ryan Spaeth <rspaeth@spaethtech.com>
 * @copyright 2022, Spaeth Technologies Inc.
 */
trait Dimension
{
    //use Rendering;

    /**
     * @param Request $request
     * @param Response $response
     * @param string $value
     *
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws HttpNotFoundException
     * @throws ImagickException
     * @throws NotFoundExceptionInterface
     * @noinspection HtmlUnknownTag
     */
    public function dim(Request $request, Response $response, string $value): Response
    {
        /** @var ImageController $this */

        $pattern = "/^(?<w>[0-9.]+)(?:[x,](?<h>[0-9.]+))?$/";

        if (!preg_match($pattern, $value, $params))
            $params = [ "w" => 0, "h" => 0 ];
        else
            $params = array_map("intval", array_filter($params, "is_string", ARRAY_FILTER_USE_KEY));

        return $this->render($request, $response,
            function(Image /* & */ $image, ...$params)
            {
                extract($params);
                $h ??= $w;

                $iw = $image->imagick->getImageWidth();
                $ih = $image->imagick->getImageHeight();
                $w  = ($w === 0) ? $iw : $w;
                $h  = ($h === 0) ? $ih : $h;

                if ($w === $iw && $h === $ih)
                    return false;

                return $image->imagick->scaleImage($w, $h);
            },
            ...$params
        );

    }
}
