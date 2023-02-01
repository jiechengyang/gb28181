<?php


namespace Biz\LiveWebSocket\Listeners;


use Biz\LiveWebSocket\BaseListener;
use Biz\LiveWebSocket\ListenerInterface;
use process\Websocket;
use Workerman\Connection\TcpConnection;

class CloseListener extends BaseListener implements ListenerInterface
{
    public function execute()
    {
        $this->websocket->deleteConnection($this->websocket->getConnection()->id);
//        echo "onClose\n";
    }
}