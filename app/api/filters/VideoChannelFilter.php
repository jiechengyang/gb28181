<?php


namespace app\sip\filters;


class VideoChannelFilter extends  \Biz\DataFilters\Resources\VideoChannelFilter
{
    protected $mode = self::SIMPLE_MODE;
    protected $simpleFields = [
        'enabled',
        'channel_name',
        'local_ip',
        'main_id',
        'device_id',
        'channel_id',
        'device_sn',
        'type_code',
        'manufacturer',
        'device_model',
        'firmware',
        'channel_num',
        'device_status',
        'ip_v4_address',
        'lastOnlineTime',
        'lastOfflineTime',
        'createdTime',
        'recorder_name',
        'third_party_name',
        'local_ip_v4'
    ];
}