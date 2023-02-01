<?php


namespace Biz\Record\Dao\Impl;


use Biz\Record\Dao\RecordPlanRangeDao;
use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;

class RecordPlanRangeDaoImpl  extends AdvancedDaoImpl implements RecordPlanRangeDao
{
    protected $table = 'smp_record_plan_range';

    public function findInByPlanIds(array $ids)
    {
        return $this->findInField('record_plan_id', $ids);
    }

    /**
     * @param $planId
     * @return array[]
     */
    public function findAllByPlanId($planId)
    {
        return $this->findByFields(['record_plan_id' => $planId]);
    }

    public function declares()
    {
        return [
            'serializes' => [
            ],
            'orderbys' => [
                'id'
            ],
            'conditions' => [
                'id = :id',
                'id > :id_GT',
                'id IN ( :ids)',
                'id NOT IN ( :noIds)',
                'record_plan_id = :recordPlanId',
                'week_day IN (:weekDays)'
            ],
            'timestamps' => [
                'created_time',
                'updated_time',
            ],
        ];
    }
}