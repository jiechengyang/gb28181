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
    'debug' => envHelper('APP_DEBUG', true),
    'default_timezone' => 'Asia/Shanghai',
    'biz_config' => [
        'redis.options' => [
            'host' => envHelper('REDIS_HOST'),
        ],
        'debug' => envHelper('APP_DEBUG', true),
        'log_dir' => dirname(__DIR__) . '/runtime/biz/logs',
        'run_dir' => dirname(__DIR__) . '/runtime/biz/run',
        'cache_directory' => dirname(__DIR__) . '/runtime/biz/cache',
        'lock.flock.directory' => dirname(__DIR__) . '/runtime/biz/lock',
    ],
    'ak_config' => [
        'api_url' => EnvHelper('AK_WEB_API', 'http://127.0.0.1:5800/'),
        'access_key' => EnvHelper('AK_ACCESS_KEY', '047I4WS1-U51UBO6W-1J4BT21P-MF17IT99-92J8WIHU-944Q4KIW'),
        'zlmediakit_api' => EnvHelper('ZLMEDIAKIT_API', 'http://127.0.0.1:18000/index/api/'),
        'zlmediakit_secret' => EnvHelper('ZLMEDIAKIT_SECRET', '035c73f7-bb6b-4889-a715-d9eb2d1925cc'),
        'debug' => envHelper('SDK_API_DEBUG', true),
        'zlm_local_ips' => envHelper('ZLM_LOCAL_IPS', "127.0.0.1|192.168.*.*"),
        'zlm_local_host' => envHelper('ZLM_LOCAL_IP', null),
        'zlm_public_host' => envHelper('ZLM_PUBLIC_HOST', null),
        'zlm_rtmp_port' => envHelper('ZLM_RTMP_PORT', 1935),
        'record_file_proxy_url' => envHelper('RECORD_FILE_URL_USE_PROXY_URL', null),
        'local_ips' => envHelper('LOCAL_IPS', null)
    ],
    'admin' => [
        'no_required_auth_routes' => [
            'admin/test/*',
            'admin/auth/captcha',
        ]
    ]
];
