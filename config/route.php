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

use Webman\Route;

//Route::group('/admin', function () {
//    $adminNameSpace = "app\\admin\\controller\\";
//    Route::any('/index', "{$adminNameSpace}Index@index");
//});
//Route::group('/api', function () {
//    $apiNameSpace = "app\\api\\controller\\";
//    Route::post('/token', sprintf("%sToken@create", $apiNameSpace));
//});
// 关闭默认路由
//Route::disableDefaultRoute();

// Route::group('/sipGateway', function () {
//     $apiNameSpace = "app\\api\\resource\\sip\\";
//     Route::any('/live/address', sprintf("%sLive@address", $apiNameSpace));
// });