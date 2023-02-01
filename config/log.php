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
    'default' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/webman.log',
                    0,
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ]
        ],
    ],
    'sip-app' => [
        'handlers' => [
            [
                // handler类的名字
                'class' => Monolog\Handler\RotatingFileHandler::class,
                // handler类的构造函数参数
                'constructor' => [
                    runtime_path() . '/logs/sip/application.log',
                    0,
                    Monolog\Logger::DEBUG,
                ],
                // 格式相关
                'formatter' => [
                    // 格式化处理类的名字
                    'class' => Monolog\Formatter\LineFormatter::class,
                    // 格式化处理类的构造函数参数
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ]
        ],
    ],
    'aKStream-bLiveHock' => [
        'handlers' => [
            [
                // handler类的名字
                'class' => Monolog\Handler\RotatingFileHandler::class,
                // handler类的构造函数参数
                'constructor' => [
                    runtime_path() . '/logs/ak-hock/hock.log',
                    0,
                    Monolog\Logger::INFO,
                ],
                // 格式相关
                'formatter' => [
                    // 格式化处理类的名字
                    'class' => Monolog\Formatter\LineFormatter::class,
                    // 格式化处理类的构造函数参数
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ]
        ],
    ],
    'task-record' => [
        'handlers' => [
            [
                'class' => Monolog\Handler\RotatingFileHandler::class,
                'constructor' => [
                    runtime_path() . '/logs/task-record.log',
                    0,
                    Monolog\Logger::DEBUG,
                ],
                'formatter' => [
                    'class' => Monolog\Formatter\LineFormatter::class,
                    'constructor' => [null, 'Y-m-d H:i:s', true],
                ],
            ]
        ],
    ]
];