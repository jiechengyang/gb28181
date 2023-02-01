<?php

namespace Biz\Role\Dao\Impl;

use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;
use Biz\Role\Dao\RoleDao;

class RoleDaoImpl extends AdvancedDaoImpl implements RoleDao 
{

    protected $table = 'smp_role';

    public function getByCode($code)
    {
        return $this->getByFields(array('code' => $code));
    }

    public function findByCodes($codes)
    {
        return $this->findInField('code', $codes);
    }

    public function getByName($name)
    {
        return $this->getByFields(array('name' => $name));
    }

    public function declares()
    {
        return [
            'serializes' => [
                'data' => 'json',
                'data_v2' => 'json',
           ], 
            'orderbys' => [ 
                'id',
                'createdTime',
                'updatedTime',
           ], 
            'conditions' => [
                'name = :name',
                'code = :code',
                'code NOT IN (:excludeCodes)',
                'code LIKE :codeLike',
                'name LIKE :nameLike',
                'createdUserId = :createdUserId',
           ], 
            'timestamps' => [ 
                'createdTime',
                'updatedTime',
           ], 
        ];
    } 
}
