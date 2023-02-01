<?php

namespace app\admin\filters;

use Biz\DataFilters\Filter;

class TokenFilter extends Filter
{
    protected $publicFields = ['token', 'user', 'success'];

    protected function publicFields(&$data)
    {
        $userFilter = new UserFilter();
        $userFilter->setMode(Filter::AUTHENTICATED_MODE);
        $userFilter->filter($data['user']);
    }
}