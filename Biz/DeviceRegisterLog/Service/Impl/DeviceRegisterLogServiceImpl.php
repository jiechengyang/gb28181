<?php


namespace Biz\DeviceRegisterLog\Service\Impl;


use Biz\BaseService;
use Biz\DeviceRegisterLog\Dao\DeviceRegisterLogDao;
use Biz\DeviceRegisterLog\Service\DeviceRegisterLogService;
use Biz\GB28281\DeviceStatus;
use Biz\VideoChannels\Service\VideoChannelsService;
use Biz\VideoRecorder\Service\VideoRecorderService;
use Codeages\Biz\Framework\Event\Event;
use Codeages\Biz\Framework\Service\Exception\ServiceException;
use support\utils\ArrayToolkit;
use Symfony\Component\EventDispatcher\GenericEvent;

class DeviceRegisterLogServiceImpl extends BaseService implements DeviceRegisterLogService
{
    public function countLogs(array $conditions = [])
    {
        return $this->getDeviceRegisterLogDao()->count($conditions);
    }

    public function searchLogs(array $conditions = [], array $orderby = [], $start, $limit, array $columns = [])
    {
        return $this->getDeviceRegisterLogDao()->search($conditions, $orderby, $start, $limit, $columns);
    }

    public function createRegisterLog($fields)
    {
        $fields = ArrayToolkit::parts($fields, [
            'deviceId',
            'ipAddress',
            'registerTime',
            'isReady',
            'data',
            'type',
        ]);

        if (!ArrayToolkit::requireds($fields, ['deviceId', 'registerTime', 'type', 'data'])) {
            throw new ServiceException("缺少必填字段:deviceId、registerTime、type、data;请检查");
        }

        $log = $this->getDeviceRegisterLogDao()->create($fields);
        // TODO: 目前不需要这样处理
//        if ('registered' === $log['type']) {
//            $this->dispatchEvent('device.register', new Event($log));
//        } else {
//            $this->dispatchEvent('device.unRegister', new Event($log));
//        }
        if ('registered' === $log['type']) {
            $this->changeDeviceStatus($log['deviceId'], $log['registerTime'], DeviceStatus::STATUS_ONLINE);
        } else {
            $this->changeDeviceStatus($log['deviceId'], $log['registerTime'], DeviceStatus::STATUS_OFFLINE);
        }
        return $log;
    }

    protected function changeDeviceStatus($deviceId, $time, $deviceStatus)
    {
        $videoRecorder = $this->getVideoRecorderService()->getVideoRecorderByDeviceId($deviceId);
        $key = $deviceStatus === DeviceStatus::STATUS_ONLINE ? 'lastOnlineTime' : 'lastOfflineTime';
        if (!empty($videoRecorder)) {
            return $this->getVideoRecorderService()->updateVideoRecorder($videoRecorder['id'], [
                'status' => $deviceStatus,
                $key => $time
            ]);
        }

        $videoChannel = $this->getVideoChannelsService()->getVideoChannelByDeviceId($deviceId);
        if (!empty($videoChannel)) {
            return $this->getVideoChannelsService()->updateStatusOnRegisterOrUnregister($videoChannel['id'], $deviceStatus, [
                $key => $time
            ]);
        }

        return null;
    }

    /**
     * @return VideoRecorderService
     */
    protected function getVideoRecorderService()
    {
        return $this->createService('VideoRecorder:VideoRecorderService');
    }

    /**
     * @return VideoChannelsService
     */
    protected function getVideoChannelsService()
    {
        return $this->createService('VideoChannels:VideoChannelsService');
    }

    /**
     * @return DeviceRegisterLogDao
     */
    protected function getDeviceRegisterLogDao()
    {
        return $this->createDao("DeviceRegisterLog:DeviceRegisterLogDao");
    }
}