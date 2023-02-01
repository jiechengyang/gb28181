<?php


namespace Biz\LiveWebSocket;


use Biz\LiveWebSocket\Listeners\ConnectListener;
use Codeages\Biz\Framework\Context\Biz;
use process\Websocket;
use support\bootstrap\BizInit;
use support\bootstrap\Container;

class LiveWebSocketFactory
{
    /**
     * @param $type
     * @param Websocket $websocket
     * @return mixed
     */
    public static function createListener($type, Websocket $websocket)
    {
        $classname = sprintf("%sListener", ucfirst($type));
        $namespace = __NAMESPACE__ . "\\Listeners";
        $classname = sprintf("%s\\%s", $namespace, $classname);
        if (!class_exists($classname)) {
            throw new \InvalidArgumentException("$classname not exist");
        }

//        $biz = Container::get(Biz::class);
        $biz = BizInit::init();
        return new $classname($biz, $websocket);

//        return $classname::getInstance($biz, $websocket);
    }
}