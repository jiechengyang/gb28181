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


return [
    // 文件更新检测
    'monitor' => [
        'handler' => process\FileMonitor::class,
        'reloadable' => false,
        'constructor' => [
            // 监控这些目录
            'monitor_dir' => [
                app_path(),
                config_path(),
                base_path() . '/process',
                base_path() . '/support',
                base_path() . '/resource',
                base_path() . '/Biz',
            ],
            // 监控这些后缀的文件
            'monitor_extenstions' => [
                'php', 'html', 'htm', 'env'
            ]
        ]
    ],
//    'task' => [
//        'handler' => process\SonarQubeTask::class
//    ],
    'redis_consumer' => [
        'handler' => Webman\RedisQueue\Process\Consumer::class,
        'count' => 1, // 可以设置多进程
        'constructor' => [
            'consumer_dir' => biz_path() . '/Queue/Job'// 消费者类目录
        ]
    ],
//    'websocket_live'  => [
//        'handler'  => process\Websocket::class,
//        'listen' => 'websocket://0.0.0.0:8888',
//        'count'  => 2,
//    ],
    'global_data' => [
        'handler' => \GlobalData\Server::class,
        'listen' => 'frame://127.0.0.1:2207'
    ],
    'task_record' => [
        'handler' => \process\TaskRecord::class,
        'count' => \envHelper('TASK_RECORD_PROCESS_NUM', 3),
    ],
    'task_device_status' => [
        'handler' => \process\TaskDeviceStatus::class,
        'count' => 1
    ]
];
