<?php

namespace Biz\Record\Dao\Impl;

use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;
use Biz\Record\Dao\RecordPlanDao;

class RecordPlanDaoImpl extends AdvancedDaoImpl implements RecordPlanDao
{

    protected $table = 'smp_record_plan';

    public function getByNameAndPartnerId($name, $partnerId)
    {
        return $this->getByFields(['name' => $name, 'partner_id' => $partnerId]);
    }

    public function getByName($name)
    {
        return $this->getByFields(['name' => $name]);
    }

    public function declares()
    {
        return [
            'serializes' => [
            ],
            'orderbys' => [
                'id',
                'created_time'
            ],
            'conditions' => [
                'id = :id',
                'id > :id_GT',
                'id IN ( :ids)',
                'id NOT IN ( :noIds)',
                'name = :name',
                'name LIKE :nameLike',
                'partner_id = :partnerId',
                'status = :status'
            ],
            'timestamps' => [
                'created_time',
                'updated_time',
            ],
        ];
    }
}
