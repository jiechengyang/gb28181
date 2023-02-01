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

use Codeages\Biz\Framework\Context\Biz;

//use function DI\value;

// 如果你需要自动依赖注入(包括注解注入)。
// 请先运行 composer require php-di/php-di && composer require doctrine/annotations
// 并将下面的代码注释解除，并注释掉最后一行 return new Webman\Container;
/*$builder = new \DI\ContainerBuilder();
$builder->addDefinitions(config('dependence', []));
$builder->useAutowiring(true);
$builder->useAnnotations(true);
return $builder->build();*/

$container = new \Biz\Container();

$bizConfig = \config('app.biz_config');
if (!empty($bizConfig)) {
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
    $container->set(Biz::class, ['values' => $options]);
}

return $container;
