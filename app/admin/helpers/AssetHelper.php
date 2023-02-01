<?php

namespace app\admin\helpers;

class AssetHelper
{
    public static function getFurl($path, $defaultKey = null)
    {
        $file = \uploads_path() . DIRECTORY_SEPARATOR . $path;
        if (is_file($file)) {
            return \Request()->uri() . $path;
        }

        if (!empty($defaultKey)) {
            $file = \static_assets_path() . DIRECTORY_SEPARATOR . 'image' . DIRECTORY_SEPARATOR . $defaultKey . '.png';
            if (is_file($file)) {
                return \Request()->uri() . "/assets/images/{$defaultKey}.png";
            }
        }

        return '';
    }

    public static function uriForPath($path)
    {
        return \Request()->uri() . $path;
    }

    public static function getScheme()
    {
        return \Request()->host();
    }
}