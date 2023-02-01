<?php


namespace app\admin\filters;


use Biz\Constants;
use Biz\DataFilters\Filter;

class RecordPlanFilter extends Filter
{
    protected $simpleFields = [
        'id',
        'partner_id',
        'name',
        'status',
        'remark',
        'limit_space',
        'limit_days',
        'over_step_plan',
        'created_time',
        'updated_time'
    ];

    protected $publicFields = [
        'id',
        'partner_id',
        'name',
        'status',
        'remark',
        'limit_space',
        'limit_days',
        'over_step_plan',
        'created_time',
        'updated_time',
        'plan_ranges',
        'plan_range_text',
        'over_step_text',
        'status_text'
    ];

    protected function simpleFields(&$data)
    {
        $this->filterLimitSpace($data);
    }

    protected function publicFields(&$data)
    {
        $this->filterLimitSpace($data);
        $data['status_text'] = Constants::getEnableOrDisableItems($data['status']);
        $data['status'] = boolval($data['status']);
        $data['over_step_text'] = Constants::getRecordePlanOverStepTypes($data['over_step_plan']);
    }

    protected function filterLimitSpace(&$data)
    {
        $data['limit_space'] /= 1024 * 1024 * 1024;
    }
}