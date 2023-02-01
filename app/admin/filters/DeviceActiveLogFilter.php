<?php


namespace app\admin\filters;


use Biz\Constants;
use Biz\DataFilters\Filter;

class DeviceActiveLogFilter extends Filter
{
    protected $simpleFields = [
        'id',
        'deviceId',
        'keepAliveTime',
        'lostTimes',
        'createdTime',
    ];

    protected $publicFields = [
        'id',
        'deviceId',
        'keepAliveTime',
        'lostTimes',
        'createdTime',
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
        !empty($data['keepAliveTime']) && $data['keepAliveTime'] = date('c', $data['keepAliveTime']);
    }
}