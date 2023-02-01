<?php


namespace process;


use Biz\Constants;
use Biz\VideoChannels\Service\VideoChannelsService;
use Workerman\Crontab\Crontab;

class TaskDeviceStatus extends AbstractProcess
{
    /**
     * TODO: 后期这个时间可以调节得更小，估计5~10分钟
     */
    const DEFAULT_TIME_DIFF = 7200;

    public function onWorkerStart()
    {
        new Crontab('*/30 * * * * *', function () {
            $this->keepDeviceStatus();
        });
    }

    /**
     * 当 sip 网关错误后，业务端无法接收到hock通知，则通过该定时任务来维护设备状态，将在线的设备处理为掉线
     */
    protected function keepDeviceStatus()
    {
        $onLineVideoChannels = $this->getVideoChannelsService()
            ->searchVideoChannels([
//                'deviceStatus' => Constants::DEVICE_STATUS_ONLINE,
                'enabled' => 1,
            ], [], 0, PHP_INT_MAX, ['id', 'lastOnlineTime']);
        $updIds = [];
        foreach ($onLineVideoChannels as $onLineVideoChannel) {
            if ($onLineVideoChannel['lastOnlineTime'] && (time() - $onLineVideoChannel['lastOnlineTime']) >= self::DEFAULT_TIME_DIFF) {
                $updIds[] = $onLineVideoChannel['id'];
            }
        }
        if (!empty($updIds)) {
            $this->getVideoChannelsService()->batchUpdateDeviceStatus($updIds, Constants::DEVICE_STATUS_OFFLINE);
        }
    }

    /**
     * @return VideoChannelsService
     */
    protected function getVideoChannelsService()
    {
        return $this->getBiz()->service('VideoChannels:VideoChannelsService');
    }
}