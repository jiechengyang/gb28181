<?php


namespace Biz\DataFilters\Resources;


use Biz\Constants;
use Biz\DataFilters\Filter;

class VideoRecorderFilter extends Filter
{
    protected $simpleFields = [
        'device_name',
        'device_id',
        'device_sn',
        'type_code',
        'manufacturer',
        'device_model',
        'firmware',
        'channel_num',
        'status',
        'local_ip',
        'address',
        'lastOnlineTime',
        'lastOfflineTime',
        'recorder_name',
    ];

    protected $publicFields = [
        'id',
        'device_name',
        'device_id',
        'device_sn',
        'type_code',
        'manufacturer',
        'device_model',
        'firmware',
        'channel_num',
        'status',
        'local_ip',
        'local_sip_port',
        'local_http_port',
        'local_server_port',
        'net_ip',
        'net_sip_port',
        'net_http_port',
        'net_server_port',
        'address',
        'parter_id',
        'lastOnlineTime',
        'lastOfflineTime',
        'createdTime',
        'recorder_name',
        'third_party_name',
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
        !empty($data['lastOnlineTime']) && $data['lastOnlineTime'] = date('c', $data['lastOnlineTime']);
        !empty($data['lastOfflineTime']) && $data['lastOfflineTime'] = date('c', $data['lastOfflineTime']);
        $data['status_title'] = Constants::getDeviceStatusItems($data['status']);
    }
}