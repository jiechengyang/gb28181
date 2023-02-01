<?php

namespace app\admin\filters;

use Biz\Constants;
use Biz\DataFilters\Filter;

class SystemLogFilter extends Filter
{
    protected $simpleFields = [
        'id',
        'userId',
        'module',
        'action',
        'message',
        'ip',
        'level',
        'createdTime',
    ];

    protected $publicFields = [
        'id',
        'userId',
        'module',
        'action',
        'message',
        'ip',
        'level',
        'createdTime',
    ];

    protected function simpleFields(&$data)
    {

    }

    protected function publicFields(&$data)
    {
    }
}