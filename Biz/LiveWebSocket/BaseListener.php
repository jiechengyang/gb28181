<?php


namespace Biz\LiveWebSocket;


use Codeages\Biz\Framework\Context\Biz;
use process\Websocket;
use support\bootstrap\Container;
use Workerman\Connection\TcpConnection;

class BaseListener
{
    private $biz;

    protected static $_instance;

    protected $websocket;

    protected $currentClientId;

    public function __construct(Biz $biz, Websocket $websocket)
    {
        $this->biz = $biz;
        $this->websocket = $websocket;
    }

    /**
     * @return mixed
     */
//    public static function getInstance(Biz $biz, Websocket $websocket)
//    {
//        if (!static::$_instance instanceof static) {
//            static::$_instance = new static($biz, $websocket);
//        }
//
//        return static::$_instance;
//    }


    protected function createService($serviceAlias)
    {
        return $this->biz->service($serviceAlias);
    }

    public function __destruct()
    {
        $this->biz = null;
    }
}