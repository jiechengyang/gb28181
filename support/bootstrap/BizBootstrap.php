<?php

namespace support\bootstrap;

use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Codeages\Biz\Framework\Context\Biz;
use support\bootstrap\biz\DoctrineServiceProvider;
use support\bootstrap\biz\ExtensionsProvider;
use support\bootstrap\biz\MonologServiceProvider;
use support\bootstrap\biz\ServiceProvider;
use support\bootstrap\biz\DaoProvider;
use support\bootstrap\Container;
use Webman\Bootstrap;
use Workerman\Timer;

/**
 *
 *
 */
class BizBootstrap implements Bootstrap
{

    public static function start($worker)
    {
        /* @var $biz Biz */
        $biz = BizInit::init();
        $biz->register(new DoctrineServiceProvider());
        $biz->register(new \Codeages\Biz\Framework\Provider\TargetlogServiceProvider());
        $biz->register(new MonologServiceProvider(), [
            'monolog.logfile' => $biz['log_dir'] . '/' . date('Ym') . '/' . date('d') . '.log',
            'monolog.level' => $biz['debug'] ? Logger::DEBUG : Logger::INFO,
            'monolog.permission' => 0666
        ]);

        $biz->register(new ExtensionsProvider());
        $biz->register(new DaoProvider());
        $biz->register(new ServiceProvider());
        $biz->boot();
        self::dbKeepAlive($biz);
    }

    /**
     * db 存活
     * @link https://www.workerman.net/q/5923
     */
    private static function dbKeepAlive(Biz $biz)
    {
        /** @var $db Connection */
        $db = $biz['db'];
        Timer::add(3600 * 7, function () use ($db) {
            echo date('Y-m-d H:i:s') . '：db heartbeat select 1', PHP_EOL, PHP_EOL;
            $db->executeQuery('select 1');
        });
    }
}