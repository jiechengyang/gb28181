<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

use support\Request;
use support\Response;
use support\view\Raw;
use support\bootstrap\Translation;
use Webman\App;
use Webman\Config;

define('BASE_PATH', realpath(__DIR__ . '/../'));

function is_local_client($ip, $ipFilters)
{
    if (empty($ipFilters)) {
        return true;
    }

    foreach ($ipFilters as $filter) {
        if ($filter === '*' || $filter === $ip || (($pos = strpos($filter, '*')) !== false && !strncmp($ip, $filter, $pos)))
            return true;
    }

    return false;
}

function is_https_request()
{
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;

    }

    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    }

    if (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        return true;
    }

    return false;
}

/**
 * @return string
 */
function base_path()
{
    return BASE_PATH;
}

/**
 * @return string
 */
function app_path()
{
    return BASE_PATH . DIRECTORY_SEPARATOR . 'app';
}

/**
 * @return string
 */
function biz_path()
{
    return BASE_PATH . DIRECTORY_SEPARATOR . 'Biz';
}

/**
 * @return string
 */
function public_path()
{
    return BASE_PATH . DIRECTORY_SEPARATOR . 'public';
}

function uploads_path()
{
    return public_path() . DIRECTORY_SEPARATOR . 'uploads';
}

function static_assets_path()
{
    return public_path() . DIRECTORY_SEPARATOR . 'assets';
}

/**
 * @return string
 */
function config_path()
{
    return BASE_PATH . DIRECTORY_SEPARATOR . 'config';
}

/**
 * @return string
 */
function runtime_path()
{
    return BASE_PATH . DIRECTORY_SEPARATOR . 'runtime';
}

/**
 * @param int $status
 * @param array $headers
 * @param string $body
 * @return Response
 */
function response($body = '', $status = 200, $headers = array())
{
    return new Response($status, $headers, $body);
}

/**
 * @param $data
 * @param int $options
 * @return Response
 */
function json($data, $options = JSON_UNESCAPED_UNICODE, $statusCode = 200)
{
    return new Response($statusCode, ['Content-Type' => 'application/json'], json_encode($data, $options));
}

/**
 * @param $xml
 * @return Response
 */
function xml($xml)
{
    if ($xml instanceof SimpleXMLElement) {
        $xml = $xml->asXML();
    }
    return new Response(200, ['Content-Type' => 'text/xml'], $xml);
}

/**
 * @param $data
 * @param string $callback_name
 * @return Response
 */
function jsonp($data, $callback_name = 'callback')
{
    if (!is_scalar($data) && null !== $data) {
        $data = json_encode($data);
    }
    return new Response(200, [], "$callback_name($data)");
}

/**
 * @param $location
 * @param int $status
 * @param array $headers
 * @return Response
 */
function redirect($location, $status = 302, $headers = [])
{
    $response = new Response($status, ['Location' => $location]);
    if (!empty($headers)) {
        $response->withHeaders($headers);
    }
    return $response;
}

/**
 * @param $template
 * @param array $vars
 * @param null $app
 * @return string
 */
function view($template, $vars = [], $app = null)
{
    static $handler;
    if (null === $handler) {
        $handler = config('view.handler');
    }
    return new Response(200, [], $handler::render($template, $vars, $app));
}

/**
 * @return Request
 */
function request()
{
    return App::request();
}

/**
 * @param $key
 * @param null $default
 * @return mixed
 */
function config($key = null, $default = null)
{
    return Config::get($key, $default);
}

if (!function_exists('envHelper')) {
    /**
     * @param $key
     * @param null $default
     * @return array|bool|false|mixed|string
     */
    function envHelper($key, $default = null)
    {
        $value = getenv($key);

        if ($value === false) {
            // TODO @Link:https://blog.csdn.net/weixin_30618985/article/details/95339884
            return \MillionMile\GetEnv\Env::get($key, $default);;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        if (($valueLength = strlen($value)) > 1 && $value[0] === '"' && $value[$valueLength - 1] === '"') {
            return substr($value, 1, -1);
        }

        return $value;
    }
}

/**
 * @param null|string $id
 * @param array $parameters
 * @param string|null $domain
 * @param string|null $locale
 * @return string
 */
function trans(string $id, array $parameters = [], string $domain = null, string $locale = null)
{
    return Translation::trans($id, $parameters, $domain, $locale);
}

/**
 * @param null|string $locale
 * @return string
 */
function locale(string $locale)
{
    if (!$locale) {
        return Translation::getLocale();
    }
    Translation::setLocale($locale);
}

/**
 * @param $worker
 * @param $class
 */
function worker_bind($worker, $class)
{
    $callback_map = [
        'onConnect',
        'onMessage',
        'onClose',
        'onError',
        'onBufferFull',
        'onBufferDrain',
        'onWorkerStop',
        'onWebSocketConnect'
    ];
    foreach ($callback_map as $name) {
        if (method_exists($class, $name)) {
            $worker->$name = [$class, $name];
        }
    }
    if (method_exists($class, 'onWorkerStart')) {
        call_user_func([$class, 'onWorkerStart'], $worker);
    }
}

/**
 * @return int
 */
function cpu_count()
{
    if (strtolower(PHP_OS) === 'darwin') {
        $count = shell_exec('sysctl -n machdep.cpu.core_count');
    } else {
        $count = shell_exec('nproc');
    }
    $count = (int)$count > 0 ? (int)$count : 4;
    return $count;
}

function scanfiles($dir, &$data = [], $excludes = [])
{
    if (is_dir($dir)) {
        if ($path = opendir($dir)) {
            while (false !== $file = readdir($path)) {
                if ('.' === $file
                    || '..' === $file
                    || in_array($dir, $excludes)) {
                    continue;
                }
                scanfiles($dir . DIRECTORY_SEPARATOR . $file, $data);
            }
            closedir($path);
        }
    } elseif (!in_array($dir, $excludes)) {
        $data[] = $dir;
    }

    return $data;
}