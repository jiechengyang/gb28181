<?php

namespace app\admin\filters;

use Biz\Constants;
use Biz\DataFilters\Filter;

class ThirdPartyFilter extends Filter
{
    protected $simpleFields = [
        'id',
        'partner_name',
        'partner_key',
        'partner_sceret',
        'live_providers',
        'params',
        'locked',
        'lock_deadline',
        'expired_time',
        'server_ip',
        'createdTime',
    ];

    protected $publicFields = [
        'id',
        'partner_name',
        'partner_key',
        'partner_sceret',
        'live_providers',
        'params',
        'locked',
        'lock_deadline',
        'expired_time',
        'server_ip',
        'createdTime',
    ];

    protected function simpleFields(&$data)
    {
        !empty($data['expired_time']) && $data['expired_time'] = date('c', $data['expired_time']);
        $this->liveProvidersFilter($data);

    }

    protected function publicFields(&$data)
    {
        !empty($data['expired_time']) && $data['expired_time'] = date('c', $data['expired_time']);
        $this->liveProvidersFilter($data);
    }

    protected function liveProvidersFilter(&$data)
    {
        $liveProviderTitles = [];
        foreach($data['live_providers'] as $liveProvider) {
            $liveProviderTitles[] = Constants::getLiveProviderItems($liveProvider);
        }

        $data['live_provider_titles'] = $liveProviderTitles;
    }
}