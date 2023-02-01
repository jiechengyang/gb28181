<?php


namespace Biz\LiveWebSocket;


use process\Websocket;
use Workerman\Connection\TcpConnection;

interface ListenerInterface
{
    public function execute();
}