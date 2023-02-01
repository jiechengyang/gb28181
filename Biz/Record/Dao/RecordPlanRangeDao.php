<?php


namespace Biz\Record\Dao;


use Codeages\Biz\Framework\Dao\AdvancedDaoInterface;

interface RecordPlanRangeDao extends AdvancedDaoInterface
{
    /**
     * @param $planId
     * @return array[]
     */
    public function findAllByPlanId($planId);

    public function findInByPlanIds(array $ids);
}