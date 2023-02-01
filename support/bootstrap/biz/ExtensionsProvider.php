<?php

namespace support\bootstrap\biz;

use Biz\AkStreamSdk\AkStreamSdk;
use Biz\LiveProvider\LiveProviderFactory;
use Biz\LiveProvider\Strategy\Impl\BLiveStrategy;
use Biz\LiveProvider\Strategy\Impl\ISecureCenterStrategy;
use Biz\LiveProvider\Strategy\Impl\Ys7Strategy;
use Biz\Queue\Driver\RedisQueue;
use Biz\Setting\Service\SettingService;
use Biz\SipGatewaySignature\SipAkSkSignture;
use Biz\User\Register\Common\RegisterTypeToolkit;
use Biz\User\Register\Impl\BaseRegister;
use Biz\User\Register\Impl\EmailRegDecoderImpl;
use Biz\User\Register\Impl\MobileRegDecoderImpl;
use Biz\User\Register\RegisterFactory;
use Biz\Ys7Sdk\OpenYs7;
use Codeages\Biz\Framework\Context\Biz;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Biz\NotificationCenter\CreateSenderFactory;
use support\bootstrap\Redis;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Webman\Config;

class ExtensionsProvider implements ServiceProviderInterface
{
    public function register(Container $biz)
    {
//        $biz['notification.dingding'] = function ($biz) {
//            return new CreateSenderFactory('DingDing');
//        };

        $biz['notification.email'] = function ($biz) {
            return new CreateSenderFactory('Email');
        };

        $biz['sip.aksk_api_sign_check'] = function ($biz) {
            return new SipAkSkSignture($biz);
        };

        $biz['sip.ak_stream_sdk'] = function ($biz) {
            /** @var $biz Biz */
            /** @var $settingService SettingService */
            $settingService = $biz->service('Setting:SettingService');

            return new AkStreamSdk($biz, $settingService->getAkServerConfig());
//            return AkStreamSdk::getInstance($biz, $settingService->getAkServerConfig());
        };

//        $biz['sip.ys7_sdk'] = function ($biz) {
//            return new OpenYs7($biz);
//        };

        $biz['queue.connection.redis'] = function ($biz) {
            return new RedisQueue('redis', $biz);
        };

        $biz['live_provider_factory'] = function ($biz) {
            return new LiveProviderFactory($biz);
        };

        $biz['live_provider.Ys7'] = function ($biz) {
            return new Ys7Strategy($biz);
        };

        $biz['live_provider.BLive'] = function ($biz) {
            return new BLiveStrategy($biz);
        };

        $biz['live_provider.ISecureCenter'] = function ($biz) {
            return new ISecureCenterStrategy($biz);
        };

        $biz['redis.api.cache'] = function ($biz) {
            return Redis::connection('apiCache');
        };

        $biz['api.security.token_storage'] = function ($biz) {
            return new TokenStorage();
        };

        $biz['user.register'] = function ($biz) {
            return new RegisterFactory($biz);
        };

        $biz['user.register.email'] = function ($biz) {
            return new EmailRegDecoderImpl($biz);
        };

        $biz['user.register.mobile'] = function ($biz) {
            return new MobileRegDecoderImpl($biz);
        };

        $biz['user.register.type.toolkit'] = function ($biz) {
            return new RegisterTypeToolkit();
        };
    }
}
