<?php


namespace Biz\Queue\Job;


use Biz\Queue\BaseDeviceJob;
use Biz\Queue\BaseJob;
use Webman\RedisQueue\Consumer;

class UpdateSipDeviceChannelJob extends BaseDeviceJob implements Consumer
{
    public $queue = 'sip:update-channel';

    public $connection = 'default';

    public function consume($data)
    {
        if (empty($data['devices']) || empty($data['formData'])) {
            return false;
        }

        try {
            $this->getVideoChannelsService()->batchUpdateAkVideoChannel($data['devices'], $data['formData']);
        } catch (\Exception $e) {
            $this->getLogService()->error('video-channel', 'batch-update-channel-error', $e->getMessage());
        }
    }
}