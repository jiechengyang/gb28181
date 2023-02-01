<?php


namespace app\admin\filters;


use Biz\Constants;

class RecordVideoChannelFilter extends VideoChannelFilter
{
    protected $publicFields = [
        'id',
        'main_id',
        'media_server_id',
        'vhost',
        'app',
        'channel_name',
        'device_id',
        'channel_id',
        'updatedTime',
        'record_plan_id',
        'record_plan_name',
        'record_status',
        'record_status_text',
    ];

    protected function publicFields(&$data)
    {
        parent::publicFields($data);
        $data['record_status_text'] = Constants::getVideoChannelRecordStatusItems($data['record_status']);
    }
}