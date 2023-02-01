<?php


namespace app\admin\filters;


use Biz\Constants;
use Biz\DataFilters\Filter;

class DeviceRegisterLogFilter extends Filter
{
    protected $simpleFields = [
        'id',
        'deviceId',
        'type',
        'isReady',
        'data',
        'registerTime',
        'createdTime',
        'ipAddress',
    ];

    protected $publicFields = [
        'id',
        'deviceId',
        'type',
        'isReady',
        'data',
        'registerTime',
        'createdTime',
        'ipAddress',
    ];

    protected function simpleFields(&$data)
    {
        $this->commonFields($data);
    }

    protected function publicFields(&$data)
    {
        $this->commonFields($data);
    }

    private function commonFields(&$data)
    {
        !empty($data['registerTime']) && $data['registerTime'] = date('c', $data['registerTime']);
        $data['type_title'] = Constants::getDevicePushStatusItems($data['type']);
        $data['ready_title'] = $data['isReady'] ? Constants::getYesOrNoItems($data['isReady']) : '';
        if (!empty($data['data'])) {
            $data['data'] = json_encode(unserialize($data['data']));
        }
    }
}