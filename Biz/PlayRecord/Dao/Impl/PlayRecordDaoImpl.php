<?php

namespace Biz\PlayRecord\Dao\Impl;

use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;
use Biz\PlayRecord\Dao\PlayRecordDao;

class PlayRecordDaoImpl extends AdvancedDaoImpl implements PlayRecordDao
{

    protected $table = 'smp_play_record';

    public function declares()
    {
        return [
            'serializes' => [
            ],
            'orderbys' => [
                'id',
                'createdTime',
            ],
            'conditions' => [
                'id =: id',
                'id > :id_GT',
                'id IN ( :ids)',
                'id NOT IN ( :noIds)',
                'startTime >= :startTime',
                'startTime <= :endTime',
                '(code LIKE :keywordsLike OR client_ip LIKE :keywordsLike)'
            ],
            'timestamps' => [
                'createdTime',
            ],
        ];
    }
}
