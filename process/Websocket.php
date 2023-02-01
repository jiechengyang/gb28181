<?php
/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace process;

use Workerman\Connection\TcpConnection;
use Biz\LiveWebSocket\LiveWebSocketFactory;
use \GlobalData;

class Websocket
{

    /**
     * @var TcpConnection
     */
    private $connection;

    /**
     * @var
     */
    private $connectHttpBuffer;
    /**
     * @var
     */
    private $messageData;

    public $stage;

    private static $globalData = null;

    public function __construct()
    {
        if (!self::$globalData) {
            self::$globalData = new GlobalData\Client('127.0.0.1:2207');
            self::$globalData->add('ws_connection_number', 0);
        }
    }


    /**
     * @param TcpConnection $connection
     * @return mixed
     */
    public function onConnect(TcpConnection $connection)
    {
//        $this->setConnection($connection);
//        $this->stage = 'connect';
//
//        return LiveWebSocketFactory::createListener('Connect', $this)->execute();
    }

    /**
     * @param TcpConnection $connection
     * @param $httpBuffer
     * @return mixed
     */
    public function onWebSocketConnect(TcpConnection $connection, $httpBuffer)
    {
        self::$globalData->increment('ws_connection_number');

        echo "客户端当前连接数：", self::$globalData->ws_connection_number, PHP_EOL;
//        $this->setConnection($connection);
//        $this->setConnectHttpBuffer($httpBuffer);
//        $this->stage = 'webSocketConnect';
//
//        return LiveWebSocketFactory::createListener('WebSocketConnect', $this)->execute();
    }

    /**
     * @param TcpConnection $connection
     * @param $data
     * @return mixed
     */
    public function onMessage(TcpConnection $connection, $data)
    {
//        $this->setConnection($connection);
//        $this->setMessageData($data);
//        $this->stage = 'message';
//
//        return LiveWebSocketFactory::createListener('Message', $this)->execute();
    }

    /**
     * @param TcpConnection $connection
     * @return mixed
     */
    public function onClose(TcpConnection $connection)
    {
        self::$globalData->increment('ws_connection_number', -1);
        echo "客户端当前2连接数：", self::$globalData->ws_connection_number, PHP_EOL;
//        $this->setConnection($connection);
//        $this->stage = 'close';
//
//        LiveWebSocketFactory::createListener('Close', $this)->execute();
    }


    /**
     * @return array
     */
    public function getConnections(): array
    {
        return self::$connections;
    }

    /**
     * @param TcpConnection $connection
     */
    public function pushConnections(TcpConnection $connection): void
    {
//        self::$connections[$connection->id] = $connection;
        echo '客户端:', ++self::$connectionNumber, PHP_EOL;//'---内存：' . $this->convert(memory_get_usage())
    }


    /**
     * @return TcpConnection
     */
    public function getConnection(): TcpConnection
    {
        return $this->connection;
    }

    /**
     * @param TcpConnection $connection
     */
    public function setConnection(TcpConnection $connection): void
    {
        $this->connection = $connection;
    }

    /**
     * @return mixed
     */
    public function getConnectHttpBuffer()
    {
        return $this->connectHttpBuffer;
    }

    /**
     * @param mixed $connectHttpBuffer
     */
    public function setConnectHttpBuffer($connectHttpBuffer): void
    {
        $this->connectHttpBuffer = $connectHttpBuffer;
    }

    /**
     * @return mixed
     */
    public function getMessageData()
    {
        return $this->messageData;
    }

    /**
     * @param mixed $messageData
     */
    public function setMessageData($messageData): void
    {
        $this->messageData = $messageData;
    }

    public function deleteConnection(string $clientId)
    {
        if (isset(self::$connections[$clientId])) {
            unset(self::$connections[$clientId]);
            return true;
        }

        return false;
    }

    private function convert($size)
    {
        $unit = array('b', 'kb', 'mb', 'gb', 'tb', 'pb');
        return @round($size / pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $unit[$i];
    }
}
