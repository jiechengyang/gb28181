<?php


namespace Biz\LiveWebSocket\Listeners;


use Biz\LiveWebSocket\BaseListener;
use Biz\LiveWebSocket\ListenerInterface;

class WebSocketConnectListener extends BaseListener implements ListenerInterface
{
    public function execute()
    {
        $this->websocket->pushConnections($this->websocket->getConnection());
//        echo "onWebSocketConnect\n";

    }
}