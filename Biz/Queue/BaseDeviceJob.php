<?php


namespace Biz\Queue;


use Biz\LiveProvider\LiveProviderFactory;
use Biz\LiveProvider\Strategy\LiveProvider;
use Biz\VideoChannels\Service\VideoChannelsService;

class BaseDeviceJob extends BaseJob
{

    /**
     * @return VideoChannelsService
     */
    protected function getVideoChannelsService()
    {
        return $this->createService('VideoChannels:VideoChannelsService');
    }

    /**
     * @return LiveProvider
     * @throws \Codeages\Biz\Framework\Service\Exception\NotFoundException
     */
    protected function getBLiveLiveProviderStrategy()
    {
        /** @var $liveProviderFactory LiveProviderFactory */
        $liveProviderFactory = $this->getBiz()->offsetGet('live_provider_factory');

        return $liveProviderFactory->createLiveProvider('BLive');
    }
}