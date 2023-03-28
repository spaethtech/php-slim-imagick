<?php /** @noinspection PhpUnused, DuplicatedCode */
declare(strict_types=1);

namespace SpaethTech\Slim\Resources\Modifiers;

use ImagickException;
use SpaethTech\Slim\Resources\ImageResource;

/**
 * Trait Dimension
 *
 * @author    Ryan Spaeth <rspaeth@spaethtech.com>
 * @copyright 2022, Spaeth Technologies Inc.
 */
trait Dimension
{

    /**
     * @param string $params
     *
     * @return array
     */
    public function dimParams(string $params): array
    {
        $pattern = "/^(?<w>[0-9.]+)(?:[x,](?<h>[0-9.]+))?$/";

        if (!preg_match($pattern, $params, $p))
            $p = [ "w" => 0, "h" => 0 ];
        else
            $p = array_map("intval", array_filter($p, "is_string", ARRAY_FILTER_USE_KEY));

        return $p;
    }

    /**
     * @param string $params
     *
     * @return string
     */
    public function dimSuffix(string $params): string
    {
        return join("-", $this->dimParams($params));
    }

    /**
     * @param string $params
     *
     * @return bool
     * @throws ImagickException
     */
    public function dim(string $params): bool
    {
        /**
         * @var ImageResource $this
         * @var int $w
         * @var int $h
         */
        extract($this->dimParams($params));
        $h ??= $w;

        $im = $this->getImagick();
        $iw = $im->getImageWidth();
        $ih = $im->getImageHeight();
        $w  = ($w === 0) ? $iw : $w;
        $h  = ($h === 0) ? $ih : $h;

        if ($w === $iw && $h === $ih)
            return false;

        $im->scaleImage($w, $h);
        return true;
    }


}
