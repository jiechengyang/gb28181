<?php

namespace app\admin\filters;

use Biz\Constants;
use Biz\DataFilters\Filter;

class PlayRecordFilter extends Filter
{
    protected $simpleFields = [
        'id',
        'code',
        'media_server_id',
        'client_ip',
        'player_id',
        'server_port',
        'params',
        'startTime',
        'createdTime',
    ];

    protected $publicFields = [
        'id',
        'code',
        'media_server_id',
        'client_ip',
        'player_id',
        'server_port',
        'params',
        'startTime',
        'createdTime',
    ];

    protected function simpleFields(&$data)
    {
        !empty($data['startTime']) && $data['startTime'] = date('c', $data['startTime']);

    }

    protected function publicFields(&$data)
    {
        !empty($data['startTime']) && $data['startTime'] = date('c', $data['startTime']);
    }
}