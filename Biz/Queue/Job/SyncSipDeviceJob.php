<?php


namespace Biz\Queue\Job;


use Biz\Queue\BaseDeviceJob;
use Webman\RedisQueue\Consumer;

class SyncSipDeviceJob extends BaseDeviceJob implements Consumer
{
    public $queue = 'sync:sip-device';

    public $connection = 'default';

    public function consume($data)
    {
        try {
            if (empty($data['ids'])) {
                throw new \Exception("同步失败：设备ids为空");
            }

            $this->getVideoChannelsService()->syncSipDevices($data['ids']);
            $this->getLogService()->info('device', 'sync-sip-device-success', '同步成功', $data);
        } catch (\Exception $e) {
            $this->getLogService()->error('device', 'sync-sip-device-error', $e->getMessage());
        }
    }
}