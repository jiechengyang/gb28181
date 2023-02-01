<?php


namespace Biz\Queue\Job;


use Biz\Queue\BaseDeviceJob;
use Webman\RedisQueue\Consumer;

class CloseLiveIpcJob extends BaseDeviceJob implements Consumer
{
    public $queue = 'ipc:close-live';

    public $connection = 'default';

    public function consume($data)
    {
        try {
            if (empty($data['ids'])) {
                throw new \Exception("同步失败：设备ids为空");
            }

            $count = $this->getBLiveLiveProviderStrategy()->closeLiveWithCameras(['ids' => $data['ids']], ['closeAutoVideo' => true]);
            $this->getLogService()->info('device', 'close-live-ipc-success', '成功关闭直播设备id：' . implode(',', $data['ids']), ['count' => $count]);
        } catch (\Exception $e) {
            $this->getLogService()->error('device', 'close-live-ipc-error', $e->getMessage());
        }
    }
}