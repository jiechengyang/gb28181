<?php

namespace app\sip\controller;

use app\AbstractController;
use app\sip\BaseController;
use Biz\LiveLog\Service\LiveLogService;
use Biz\LiveProvider\LiveProviderFactory;
use Biz\LiveProvider\Strategy\LiveProvider;
use Biz\LiveProvider\Strategy\LiveProviderStrategy;
use Biz\SystemLog\Service\SystemLogService;
use support\exception\HttpException;
use support\Request;
use Biz\User\Service\UserService;
use Webman\Exception\NotFoundException;

class Live extends BaseController
{

    public function address(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            return json(['code' => self::ERROR_CODE_METHOD_FAILED, 'data' => null, 'message' => 'Not Found'], 404);
        }

        $options = $request->post();
        if (!isset($options['ssl']) && \is_https_request()) {
            $options['ssl'] = true;
        }

        $remoteIp = $request->getRealIp();
        // TODO: 这段代码 可能不需要
        if (\is_local_client($remoteIp, ['127.0.0.1', '192.168.*.*'])) {
            $options['intranet'] = true;
        }

        // TODO 针对内网不能访问公网端口做特殊处理，此时内网未做端口回流
        $localClientIps = config('app.ak_config.local_ips');
        if (!empty($localClientIps) && \is_local_client($remoteIp, explode('|', $localClientIps))) {
            $options['intranet'] = false;
        }

        $address = null;
        $currentPartner = $this->getCurrentPartner();
        try {
            $address = $this->getCurrentLiveProviderStrategy()->getLiveUrl($options['code'], $options);
            $msg = 'success';
            $code = 0;
            $statusCode = 200;
            $this->getLiveLogService()->createLiveLog([
                'status' => 1,
                'parter_key' => $currentPartner['partner_key'],
                'live_provider' => $this->getLiveProvider(),
                'url' => $address,
                'expireTime' => $options['expireTime'] ?? 0,
                'request_ip' => $request->getRealIp(),
            ]);
            $this->getSystemLogService()->info("sipLive", "liveUrlSuccess", "客户端：{$request->getRealIp()}获取直播地址成功", ['url' => $address, 'currentIp' => $request->getRealIp()]);
        } catch (HttpException $httpException) {
            $code = $httpException->getCode();
            $statusCode = $httpException->getStatusCode();
            $msg = $httpException->getMessage();
            $this->getSystemLogService()->error("sipLive", "liveUrlFailed", "客户端：{$request->getRealIp()}获取直播地址失败：{$msg}", ['currentIp' => $request->getRealIp()]);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            $statusCode = 500;
            $msg = $exception->getMessage();
            $this->getSystemLogService()->error("sipLive", "liveUrlFailed", "客户端：{$request->getRealIp()}获取直播地址失败：{$msg}", ['currentIp' => $request->getRealIp()]);
        }

        return json(['code' => $code, 'data' => [
            'url' => $address
        ], 'message' => $msg], null, $statusCode);
    }

    public function stop(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            return json(['code' => self::ERROR_CODE_METHOD_FAILED, 'data' => null, 'message' => 'Not Found'], 404);
        }
        $deviceCode = $request->get('code');
        if (empty($deviceCode)) {
            throw new NotFoundException("访问不存在");
        }

        try {
            $this->getCurrentLiveProviderStrategy()->stopLive($deviceCode, $request->post());
            $msg = 'success';
            $code = 0;
            $statusCode = 200;
            $this->getSystemLogService()->info("sipLive", "stopLiveSuccess", "客户端：{$request->getRealIp()}关闭直播成功", ['code' => $deviceCode, 'currentIp' => $request->getRealIp()]);
        } catch (\Exception $exception) {
            $code = $exception->getCode();
            $statusCode = 500;
            $msg = $exception->getMessage();
            $this->getSystemLogService()->error("sipLive", "stopLiveFailed", "客户端：{$request->getRealIp()}关闭直播失败：{$msg}", ['currentIp' => $request->getRealIp()]);
        }

        return json(['code' => $code, 'data' => null, 'message' => $msg], null, $statusCode);
    }

    /**
     *
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    /**
     * @return SystemLogService
     */
    protected function getSystemLogService()
    {
        return $this->createService('SystemLog:SystemLogService');
    }

    /**
     * @return LiveLogService
     */
    protected function getLiveLogService()
    {
        return $this->createService('LiveLog:LiveLogService');
    }
}
