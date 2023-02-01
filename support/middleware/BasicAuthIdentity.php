<?php


namespace support\middleware;


use Codeages\Biz\Framework\Context\Biz;
use support\bootstrap\Container;
use support\middleware\security\firewall\BasicAuthenticationListener;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class BasicAuthIdentity extends BaseAuthIdentity implements MiddlewareInterface
{
    protected $noRequiredAuthRoutes = [
        'admin/index/*'
    ];
    
    public function process(Request $request, callable $next): Response
    {
        $currentRoute = $request->path();
        if (!$this->routeIsRequiredAuth($currentRoute)) {
            return $next($request);
        }
        
        // TODO：验证auth 和basic 中间件的执行顺序
        echo 'BasicAuthIdentity', PHP_EOL;
        $basicAuthenticationListener = new BasicAuthenticationListener($this->getBiz());
        if ($request->header('authorization', null) && null === $request->header('x-auth-token', null)) {
            $basicAuthenticationListener->handle($request);
            $this->identity();
        }

        return $next($request);
    }
}