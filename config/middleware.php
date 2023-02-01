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
    '' => [
        \support\middleware\ActionHook::class,
        \support\middleware\AccessCorsControl::class,
        //support\middleware\AuthCheckTest::class,
    ],
    'admin' => [
        \support\middleware\IpCheck::class,
        \support\middleware\BasicAuthIdentity::class,
        \support\middleware\XAuthTokenIdentity::class,

    ],
    'sip' => [
        \support\middleware\IpCheck::class,
        \support\middleware\SipAkSkCheck::class,
    ]
];