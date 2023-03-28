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
use SpaethTech\Slim\Controllers\Imaging\Resizing\Dimension;
use SpaethTech\Slim\Controllers\Imaging\Resizing\Scale;
use SpaethTech\Slim\Controllers\Imaging\Resizing\Thumbnail;

//use function App\Controllers\Images\get_called_method;

/**
 * Class ImageController
 *
 * @author Ryan Spaeth <rspaeth@spaethtech.com>
 * @copyright 2022 Spaeth Technologies Inc.
 */
class ImageController extends Controller
{
    public const ROUTE_PATTERN      = "/{path:.*}/{file:.*}.{ext:gif|jpg|jpeg|png}";

    protected const REGEX_W_H       = "/^(?<width>[0-9]+)(?:x(?<height>[0-9]+))?$/";
    protected const REGEX_W_H_X_Y   = "/^(?<width>[0-9]+)(?:,(?<height>[0-9]+))?(?:,(?<x>[0-9]+))?(?:,(?<y>[0-9]+))?$/";

    use Dimension;
    use Scale;
    use Thumbnail;

    /**
     * @param Request $request
     * @param Response $response
     * @param string $path
     * @param string $file
     * @param string $ext
     *
     * @return Response
     * @throws ContainerExceptionInterface
     * @throws HttpNotFoundException
     * @throws ImagickException
     * @throws NotFoundExceptionInterface
     */
    public function __invoke(Request $request, Response $response, string $path, string $file, string $ext): Response
    {
        // Create a new Image object.
        $image = new Image($this->container, "$path/$file.$ext");

        // IF the file does not exist, THEN return a 404!
        if(!$image->exists())
            throw new HttpNotFoundException($request);

        foreach($request->getQueryParams() as $key => $value)
        {
            if (method_exists($this, $key))
                return $this->$key($request, $response, $value);
        }

        return $image->render($response);
    }


    /**
     * Renders an image given an optional callback for alteration.
     *
     * @param Request $request       The Request object.
     * @param Response $response     The Response object.
     * @param callable|null $func    A callback for image manipulation.
     * @param mixed ...$params       Any parameters to pass to the callback.
     *
     * @return Response
     *
     * @throws ContainerExceptionInterface
     * @throws HttpNotFoundException
     * @throws ImagickException
     * @throws NotFoundExceptionInterface
     *
     */
    public function render(Request $request, Response $response, callable $func = null, ...$params): Response
    {
        /**
         * Defined in ImageController::ROUTE_PATTERN
         * @var string $path        The path to the requested image.
         * @var string $file        The filename of the requested image.
         * @var string $ext         The extension of the requested image.
         */
        extract(RouteContext::fromRequest($request)->getRoute()->getArguments());

        // Create a new Image object.
        $image = new Image($this->container, "$path/$file.$ext");

        // IF the file does not exist, THEN return a 404!
        if(!$image->exists())
            throw new HttpNotFoundException($request);

        //$method = get_called_method();
        $method = Controller::getCallingMethod();

//        // IF no resizing is required...
//        if ($image->isCorrectSize(...$params))
//        {
//            // ...THEN get the original file's MIME type and actual data.
//            return $image->render($response);
//        }
        // ...OTHERWISE, IF a cached version of the correct size exists...
        if ($image->cachedExists($method, ...$params))
        {
            // ...THEN get the cached file's MIME type and actual data.
            //return $image->cachedRender($response, $method, $width, $height);
            return $image->renderCached($response, $method, ...$params);
        }
        // ...OTHERWISE, we need to resize the image on the fly...
        else
        {
            $altered = false;

            // IF a callback is provided, THEN pass the image for manipulation.
            if ($func !== null)
                $altered = $func($image, ...$params);

            if(!$altered)
                return $image->render($response);

            // Save the image to the cache folder for caching.
            $image->save($method, ...$params);

            // AND return the new image content!
            return $image
                ->renderCached($response, $method, ...$params)
                ->withStatus(201, "Created");
        }

    }


//    protected function image(Response $response, string $path): Response
//    {
//
//    }
















}
