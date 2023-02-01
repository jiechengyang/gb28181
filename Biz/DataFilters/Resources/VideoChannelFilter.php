<?php


namespace Biz\DataFilters\Resources;


use Biz\Constants;
use Biz\DataFilters\Filter;

class VideoChannelFilter extends Filter
{
    protected $publicFields = [
        'id',
        'main_id',
        'media_server_id',
        'vhost',
        'app',
        'channel_name',
        'device_network_type',
        'device_stream_type',
        'video_device_type',
        'has_ptz',
        'device_id',
        'channel_id',
        'rtp_proto',
        'default_rtp_port',
        'enabled',
        'method_by_get_stream',
        'video_src_url',
        'recorder_id',
        'parter_id',
        'lastOnlineTime',
        'lastOfflineTime',
        'createdTime',
        'device_status',
        'enabled',
        'origin_main_id',
        'locked',
        'local_ip_v4',
        'ip_v4_address',
        'close_live',
        'auto_live',
        'record_plan_id',
        'record_plan_name'
    ];

    protected function simpleFields(&$data)
    {
        $this->commonFields($data);
    }

    protected function publicFields(&$data)
    {
        $this->commonFields($data);
    }

    protected function commonFields(&$data)
    {
        !empty($data['lastOnlineTime']) && $data['lastOnlineTime'] = date('c', $data['lastOnlineTime']);
        !empty($data['lastOfflineTime']) && $data['lastOfflineTime'] = date('c', $data['lastOfflineTime']);
        isset($data['device_status']) && $data['device_status_title'] = Constants::getDeviceStatusItems($data['device_status']);
        isset($data['enabled']) && $data['enabled_title'] = Constants::getDeviceEnableStatusItems($data['enabled']);
        isset($data['local_ip_v4']) && $data['ip_v4_address'] = $data['local_ip_v4'] ?: $data['ip_v4_address'];

    }
}