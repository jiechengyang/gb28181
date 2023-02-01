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

namespace support\middleware;

use Biz\Constants;
use Biz\User\Exception\UserException;
use support\middleware\security\firewall\XAuthTokenAuthenticationListener;
use support\ServiceTrait;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class XAuthTokenIdentity extends BaseAuthIdentity implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $currentRoute = $request->path();
        if (!$this->routeIsRequiredAuth($currentRoute)) {
            return $next($request);
        }

        echo 'XAuthTokenIdentity', PHP_EOL;
        $xAuthTokenAuthenticationListener = new XAuthTokenAuthenticationListener($this->getBiz());
        try {
            $xAuthTokenAuthenticationListener->handle($request);
            $this->identity();

        } catch (\Exception $e) {
            throw $e;
        }

        return $next($request);
    }
}