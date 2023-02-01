<?php


namespace Biz\DeviceRegisterLog\Dao\Impl;


use Biz\DeviceRegisterLog\Dao\DeviceRegisterLogDao;
use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;

class DeviceRegisterLogDaoImpl extends AdvancedDaoImpl implements DeviceRegisterLogDao
{
    protected $table = 'smp_device_register_log';

    public function declares()
    {
        return [
            'orderbys' => [
                'id',
                'createdTime',
                'isReady',
                'registerTime',
                'type',
            ],
            'timestamps' => [
                'createdTime',
            ],
            'conditions' => [
                'id =: id',
                'type =: type',
                'isReady =: isReady',
                'id > :id_GT',
                'deviceId = :deviceId',
                'deviceId LIKE :deviceIdLike',
                'registerTime >= :startTime',
                'registerTime <= :endTime',
            ],
        ];
    }
}