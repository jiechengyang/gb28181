<?php


namespace support\middleware;


use Codeages\Biz\Framework\Context\Biz;
use support\bootstrap\BizInit;
use support\bootstrap\Container;
use support\ServiceTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class BaseAuthIdentity
{
    use ServiceTrait;

    protected $noRequiredAuthRoutes = [
        'admin/auth/login',
        'admin/index/*'
    ];

    protected function routeIsRequiredAuth($route)
    {
        $route = ltrim($route, '/');
        $flag = true;
        $noRequiredAuthRoutes = array_merge(\config('app.admin.no_required_auth_routes'), $this->noRequiredAuthRoutes);
        foreach ($noRequiredAuthRoutes as $noRequiredAuthRoute) {
            $parseRoute = explode('/', $noRequiredAuthRoute);
            $level = count($parseRoute);
            if ($level == 1 && current($parseRoute) === '*') {
                $flag = false;
                break;
            }

            if ($level == 2 && false !== strpos($route, $parseRoute[0]) && $parseRoute[1] === '*') {
                $flag = false;
                break;
            }

            if ($level == 3 && false !== strpos($route, $parseRoute[0] . '/' . $parseRoute[1]) && $parseRoute[2] === '*') {
                $flag = false;
                break;
            }

            if ($noRequiredAuthRoute === $route) {
                $flag = false;
                break;
            }
        }

        return $flag;
    }
    
    protected function identity()
    {
        $this->getBiz()->offsetSet('user', $this->getTokenStorage()->getToken()->getUser());
    }

    /**
     * @return TokenStorageInterface
     */
    protected function getTokenStorage()
    {
        return $this->getBiz()->offsetGet('api.security.token_storage');
    }

    /**
     * @return Biz
     */
    protected function getBiz()
    {
        return BizInit::init();
//        return Container::get(Biz::class);
    }
}