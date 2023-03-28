<?php /** @noinspection PhpUnused */
declare(strict_types=1);

namespace SpaethTech\Slim\Controllers;

use ImagickException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpNotFoundException;
use Slim\Routing\RouteContext;
use SpaethTech\Slim\Resources\ImageResource;
use SpaethTech\Slim\Resources\Resource;

/**
 * Class ImageController
 *
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 * @copyright 2022 Spaeth Technologies Inc.
 */
class ImageController extends Controller
{
    public const ROUTE_PATTERN = "/{path:.*}/{file:.*}.{ext:gif|jpg|jpeg|png}";

    #region Getters

    /**
     * @param Request $request
     *
     * @return Resource
     * @throws HttpNotFoundException
     * @throws ImagickException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    protected function getResource(Request $request): Resource
    {
        $args = RouteContext::fromRequest($request)->getRoute()->getArguments();
        $params = $request->getQueryParams();

        if (!array_key_exists("path", $args) ||
            !array_key_exists("file", $args) ||
            !array_key_exists("ext", $args))
            throw new HttpNotFoundException($request,
                "Something unexpected happened while routing with ".__CLASS__);

        $path = $args["path"];
        $file = $args["file"];
        $ext = $args["ext"];

        return new ImageResource($this, "$path/$file.$ext", $params);
    }

    #endregion

    /**
     * @param Request $request The Request object.
     * @param Response $response The Response object.
     *
     * @return Response
     *
     * @throws HttpNotFoundException
     * @throws ImagickException
     */
    public function render(Request $request, Response $response): Response
    {
        // NOTE: The following will prevent a file from being loaded from the
        // cache if the original has been deleted.

        // IF the file does not exist, THEN return a 404!
        if(!$this->resource->exists())
            throw new HttpNotFoundException($request);

        // IF a cached version exists...
        if ($this->resource->cached())
        {
            // ...THEN render the cached file!
            return $this->resource->render($response, TRUE);
        }
        else
        {
            // ...OTHERWISE, we need to resize the image on the fly...
            $alterations = [];

            // Loop through each query parameter
            foreach($request->getQueryParams() as $key => $value)
            {
                if (method_exists($this->resource, $key))
                    $alterations[] = $this->resource->$key($value);
            }

            $altered = false;

            foreach($alterations as $alteration)
                if ($alteration)
                {
                    $altered = TRUE;
                    break;
                }

            if(!$altered)
                return $this->resource->render($response);

            // Save the altered image to the cache folder for followup requests.
            $this->resource->cache();

            // AND return the new image content!
            return $this->resource
                ->render($response, TRUE)
                ->withStatus(201, "Created");
        }

    }



}
