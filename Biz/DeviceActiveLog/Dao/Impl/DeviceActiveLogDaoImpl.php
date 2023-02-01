<?php


namespace Biz\DeviceActiveLog\Dao\Impl;


use Biz\DeviceActiveLog\Dao\DeviceActiveLogDao;
use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;

class DeviceActiveLogDaoImpl extends AdvancedDaoImpl implements DeviceActiveLogDao
{
    protected $table = 'smp_device_active_log';

    public function declares()
    {
        return [
            'orderbys' => [
                'id',
                'createdTime',
                'keepAliveTime',
            ],
            'timestamps' => [
                'createdTime',
            ],
            'conditions' => [
                'id =: id',
                'id > :id_GT',
                'deviceId = :deviceId',
                'keepAliveTime >= :startTime',
                'keepAliveTime <= :endTime',
                'deviceId LIKE :deviceIdLike',
            ],
        ];
    }
}