<?php

namespace app\sip;

use app\AbstractController;
use Biz\AkStreamSdk\AkStreamSdk;
use Biz\Constants;
use Biz\LiveProvider\LiveProviderFactory;
use Biz\LiveProvider\Strategy\LiveProvider;
use support\bootstrap\Log;
use support\Request;
use support\Response;

class BaseController extends AbstractController
{

    /**
     * @var null
     */
    private $liveProvider = null;

    /**
     * @var null
     */
    private $currentThirdParty = null;

    /**
     * @param Request $request
     * @return Response|void
     */
    public function beforeAction(Request $request)
    {
        parent::beforeAction($request);
        $headers = $request->header();
        $this->setLiveProvidersByHeader($headers);
        if (empty($this->getLiveProvider())) {
            return json(['code' => 4037771, 'data' => null, 'message' => '请提供合法的云监控服务商'], JSON_UNESCAPED_UNICODE, 403);
        }

        if ('POST' !== strtoupper($request->method())) {
            return json(['code' => 4047771, 'data' => null, 'message' => 'NOT FOUND ACTION'], JSON_UNESCAPED_UNICODE, 404);
        }

        $this->setCurrentThirdPartyByHeader($headers);

        $this->getLogger()->info('sip request:', [
            'real_ip' => $request->getRealIp(),
            'remote_ip' => $request->getRealIp(),
            'headers' => $headers,
            'uri' => $request->uri(),
            'post_data' => $request->post(),
            'get_data' => $request->get(),
        ]);
    }

    /**
     * @param Request $request
     * @param $response
     */
    public function afterAction(Request $request, $response)
    {
        /** @var $response Response */
        parent::afterAction($request, $response);
        $this->getLogger()->info('sip response', [
            'statusCode' => $response->getStatusCode(),
            'body' => $response->rawBody()
        ]);
    }

    /**
     *
     * 补充合作方id查询条件
     * @param array $conditions
     */
    protected function fillPartnerId(array &$conditions)
    {
        if (empty($conditions['partnerId']) && !empty($this->currentThirdParty)) {
            $conditions['partnerId'] = $this->currentThirdParty['id'];
        }
    }


    /**
     * @param $headers
     */
    protected function setLiveProvidersByHeader($headers)
    {
        if (!empty($headers['x-live-provider'])) {
            $liveProvider = $headers['x-live-provider'];
            $privateLiveProviders = array_keys(Constants::getLiveProviderItems());
            $this->liveProvider = in_array($liveProvider, $privateLiveProviders) ? $liveProvider : null;
        }
    }


    /**
     * @return string|null
     */
    protected function getLiveProvider()
    {
        return $this->liveProvider;
    }

    /**
     * @param $headers
     */
    public function setCurrentThirdPartyByHeader($headers): void
    {
        if (!empty($headers['x-ca-key'])) {
            $cache = $this->getRedis()->get(sprintf("partner:%s", $headers['x-ca-key']));
            if (!empty($cache)) {
                $this->currentThirdParty = unserialize($cache);
            }
        }
    }

    /**
     * @return null
     */
    protected function getCurrentPartner()
    {
        return $this->currentThirdParty;
    }

    /**
     * @return LiveProvider
     * @throws \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    protected function getCurrentLiveProviderStrategy()
    {
        $type = $this->getLiveProvider();
        /** @var $liveProviderFactory LiveProviderFactory */
        $liveProviderFactory = $this->getBiz()->offsetGet('live_provider_factory');

        return $liveProviderFactory->createLiveProvider($type);
    }

    /**
     * @return \support\bootstrap\Redis
     */
    protected function getRedis()
    {
        return $this->getBiz()->offsetGet('redis.api.cache');
    }

    /**
     * @return \Monolog\Logger|null
     */
    protected function getLogger()
    {
        return Log::channel('sip-app');
    }

    /**
     * @return AkStreamSdk
     */
    protected function getAkStreamSdk()
    {
        return $this->getBiz()->offsetGet('sip.ak_stream_sdk');
    }
}