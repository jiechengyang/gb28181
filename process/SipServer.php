<?php

namespace process;

use Workerman\Connection\ConnectionInterface;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Biz\LiveWebSocket\LiveWebSocketFactory;
use \GlobalData;

class SipServer extends AbstractProcess
{
    private $config;

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function onWorkerStart(Worker $worker)
    {
        echo ' ----- SIP Server Worker gets to work, Current workerID: ' . $worker->id . '-----', PHP_EOL;
    }

    public function onWorkerReload(Worker $worker)
    {
    }

    public function onConnect(TcpConnection $connection)
    {
        echo "Current workerID: {$connection->id} & new connection from ip  {$connection->getRemoteIp()}:{$connection->getRemotePort()}", PHP_EOL;
    }

    public function onMessage(ConnectionInterface $connection, $data)
    {
        var_dump($data);
    }

    public function onClose(TcpConnection $connection)
    {
        echo 'The ip ' . $connection->getRemoteIp() . ':' . $connection->getRemotePort() . ' is Disconnect', PHP_EOL;
    }

    public function onError(TcpConnection $connection, $code, $msg)
    {

    }
}