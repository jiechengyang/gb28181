<?php

namespace Biz\LiveLog\Dao\Impl;

use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;
use Biz\LiveLog\Dao\LiveLogDao;

class LiveLogDaoImpl extends AdvancedDaoImpl implements LiveLogDao 
{

    protected $table = 'smp_live_log';

    public function declares()
    {
        return [
            'serializes' => [ 
           ], 
            'orderbys' => [ 
                'id',
                'createdTime',
                'updatedTime',
           ], 
            'conditions' => [ 
                'id =: id',
                'id > :id_GT',
                'id IN ( :ids)',
                'id NOT IN ( :noIds)',
                'createdTime >= :startTime',
                'createdTime <= :endTime',
           ], 
            'timestamps' => [ 
                'createdTime',
                'updatedTime',
           ], 
        ];
    } 
}
