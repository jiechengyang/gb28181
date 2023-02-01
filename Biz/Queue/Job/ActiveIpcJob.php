<?php


namespace Biz\Queue\Job;


use Biz\Queue\BaseDeviceJob;
use Webman\RedisQueue\Consumer;

class ActiveIpcJob extends BaseDeviceJob implements Consumer
{
    public $queue = 'ipc:active';

    public $connection = 'default';

    public function consume($data)
    {
        try {
            if (empty($data['ids'])) {
                throw new \Exception("同步失败：设备ids为空");
            }

            $result = $this->getBLiveLiveProviderStrategy()->activeAndOpenLiveWithCameras([
                'ids' => $data['ids'],
                'enabled' => 0,
            ], [], 0, PHP_INT_MAX);
            $this->getLogService()->info('device', 'active-ipc-success', "成功激活：{$result['successCount']}台设备，失败：{$result['failedCount']}台设备");
        } catch (\Exception $e) {
            $this->getLogService()->error('device', 'active-ipc-error', $e->getMessage());
        }
    }
}