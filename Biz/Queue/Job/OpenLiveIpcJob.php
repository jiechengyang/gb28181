<?php


namespace Biz\Queue\Job;


use Biz\LiveProvider\LiveProviderFactory;
use Biz\LiveProvider\Strategy\LiveProvider;
use Biz\Queue\BaseDeviceJob;
use Biz\VideoChannels\Service\VideoChannelsService;
use Webman\RedisQueue\Consumer;

class OpenLiveIpcJob extends BaseDeviceJob implements Consumer
{
    public $queue = 'ipc:open-live';

    public $connection = 'default';

    public function consume($data)
    {
        try {
            if (empty($data['ids'])) {
                throw new \Exception("同步失败：设备ids为空");
            }

            $result = $this->getBLiveLiveProviderStrategy()->openLiveWithCameras([
                'ids' => $data['ids']
            ], ['autoVideo' => true]);
            $this->getLogService()->info('device', 'open-live-ipc-success', "成功开启：{$result}台设备的自动推流直播");
        } catch (\Exception $e) {
            $this->getLogService()->error('device', 'open-live-ipc-error', $e->getMessage());
        }
    }
}