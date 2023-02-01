<?php


namespace Biz\LiveWebSocket\Listeners;


use Biz\LiveWebSocket\BaseListener;
use Biz\LiveWebSocket\ListenerInterface;
use process\Websocket;
use Workerman\Connection\TcpConnection;

class ConnectListener extends BaseListener implements ListenerInterface
{
    public function execute()
    {
//        echo "onConnect\n";
    }
}