<?php


namespace Biz\DeviceActiveLog\Service\Impl;


use Biz\BaseService;
use Biz\DeviceActiveLog\Dao\DeviceActiveLogDao;
use Biz\DeviceActiveLog\Service\DeviceActiveLogService;
use Codeages\Biz\Framework\Service\Exception\ServiceException;
use support\utils\ArrayToolkit;

class DeviceActiveLogServiceImpl extends BaseService implements DeviceActiveLogService
{
    public function countLogs(array $conditions = [])
    {
        return $this->getDeviceActiveLogDao()->count($conditions);
    }

    public function searchLogs(array $conditions = [], array $orderby, $start, $limit, array $columns = [])
    {
        return $this->getDeviceActiveLogDao()->search($conditions, $orderby, $start, $limit);
    }

    public function createActiveLog($fields)
    {
        $fields = ArrayToolkit::parts($fields, [
           'deviceId',
           'keepAliveTime',
           'lostTimes',
        ]);

        if (!ArrayToolkit::requireds($fields, ['deviceId', 'keepAliveTime'])) {
            throw new ServiceException("deviceId、keepAliveTime is required");
        }

        return $this->getDeviceActiveLogDao()->create($fields);
    }

    public function getActiveLogsByDeviceId($deviceId)
    {
        // TODO: Implement getActiveLogsByDeviceId() method.
    }

    /**
     * @return DeviceActiveLogDao
     */
    protected function getDeviceActiveLogDao()
    {
        return $this->createDao('DeviceActiveLog:DeviceActiveLogDao');
    }
}