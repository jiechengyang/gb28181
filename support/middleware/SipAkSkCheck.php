<?php

namespace support\middleware;

use Codeages\Biz\Framework\Context\Biz;
use support\bootstrap\BizInit;
use support\bootstrap\Container;
use support\exception\HttpException;
use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use Biz\SipGatewaySignature\SipAkSkSignture;

class SipAkSkCheck implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        try {
            /** @var $signCheck SipAkSkSignture */
            $signCheck = $this->getBiz()['sip.aksk_api_sign_check'];
            $result = $signCheck->check($request);

            return $next($request);
        } catch (HttpException $exception) {
            return json(['code' => $exception->getCode(), 'message' => $exception->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE, $exception->getStatusCode());
        } catch (\Exception $exception) {
            return json(['code' => $exception->getCode(), 'message' => $exception->getMessage()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE, 500);
        }
    }

    /**
     * @return Biz
     */
    protected function getBiz()
    {
        return BizInit::init();
//        return Container::get(Biz::class);
    }
}