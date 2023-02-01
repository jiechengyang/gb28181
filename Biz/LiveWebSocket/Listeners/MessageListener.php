<?php


namespace Biz\LiveWebSocket\Listeners;


use Biz\LiveWebSocket\BaseListener;
use Biz\LiveWebSocket\ListenerInterface;
use Workerman\Connection\TcpConnection;

class MessageListener extends BaseListener implements ListenerInterface
{
    public function execute()
    {
        $message = $this->websocket->getMessageData();
        $cmdMethod = sprintf("cmd%s", ucfirst($message));
        if (method_exists($this, $cmdMethod)) {
            return $this->{$cmdMethod}();
        }

        $this->websocket->getConnection()->send($message);
    }

    public function cmdCountConn()
    {
        $count = $this->websocket->getConnectionNumber();
        $this->websocket->getConnection()->send(json_encode([
            'count' => $count,
        ]));
    }

    protected function cmdBroadcast()
    {
        foreach ($this->websocket->getConnections() as $clientId => $connection) {
            /** @var $connection TcpConnection */
            if ($clientId !== $this->currentClientId) {
                $connection->send("Hello World");
            }
        }
    }

    protected function cmdCloseOther()
    {
        foreach ($this->websocket->getConnections() as $clientId => $connection) {
            /** @var $connection TcpConnection */
            if ($clientId !== $this->currentClientId) {
                $connection->close();
            }
        }
    }


    protected function parseMessage($message)
    {
    }
}