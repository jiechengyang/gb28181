<?php

namespace support\bootstrap;

use Codeages\Biz\Framework\Context\Biz;
use Monolog\Logger;
use support\bootstrap\biz\DaoProvider;
use support\bootstrap\biz\DoctrineServiceProvider;
use support\bootstrap\biz\ExtensionsProvider;
use support\bootstrap\biz\MonologServiceProvider;
use support\bootstrap\biz\ServiceProvider;
use support\bootstrap\Container;

/**
 * @todo 该类用于内存泄露问题排查，初始化的时候【config\container.php】不初始biz 则理论上在每个需要地方实例化，目前看来实现有问题
 *
 * Class BizInit
 * @package support\bootstrap
 */
class BizInit
{
    public static function init()
    {
        $biz = Container::has(Biz::class);
        if (!$biz) {
            $options = array_merge(\config('app.biz_config'), [
                'db.options' => [
                    'dbname' => \config('database.connections.mysql.database'),
                    'user' => \config('database.connections.mysql.username'),
                    'password' => \config('database.connections.mysql.password'),
                    'host' => \config('database.connections.mysql.host'),
                    'port' => \config('database.connections.mysql.port'),
                    'driver' => \config('database.connections.mysql.driver'),
                    'charset' => \config('database.connections.mysql.charset'),
                ],
            ]);
            $biz = new Biz($options);
        } else {
            /* @var $biz Biz */
            $biz = Container::get(Biz::class);
        }

        return $biz;
    }
}