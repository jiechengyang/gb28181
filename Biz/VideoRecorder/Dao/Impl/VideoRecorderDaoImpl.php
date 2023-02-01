<?php

namespace Biz\VideoRecorder\Dao\Impl;

use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;
use Biz\VideoRecorder\Dao\VideoRecorderDao;

class VideoRecorderDaoImpl extends AdvancedDaoImpl implements VideoRecorderDao 
{

    protected $table = 'smp_video_recorder';

    public function declares()
    {
        return [
            'serializes' => [ 
           ], 
            'orderbys' => [ 
                'id',
                'createdTime',
                'updatedTime',
                'lastOnlineTime',
                'status',
           ], 
            'conditions' => [ 
                'id =: id',
                'id > :id_GT',
                'id IN (:ids)',
                'id NOT IN (:noIds)',
                'createdTime >= :startTime',
                'createdTime <= :endTime',
                'device_id = :deviceId',
                'parter_id = :parterId',
                'parter_id = :partnerId',
                'parter_id != :noParterId',
                'parter_id != :noPartnerId',
                'parter_id IN (:parterIds)',
                'parter_id IN (:partnerIds)',
                'status = :status',
                'type = :type',
                '(device_name LIKE :keywordsLike OR device_id LIKE :keywordsLike OR device_sn LIKE :keywordsLike OR manufacturer LIKE :keywordsLike OR device_model LIKE :keywordsLike OR local_ip LIKE :keywordsLike OR username LIKE :keywordsLike OR address LIKE :keywordsLike)'
           ],
            'timestamps' => [ 
                'createdTime',
                'updatedTime',
           ], 
        ];
    }

    /**
     * @param $deviceId
     * @return array|null
     */
    public function findByDeviceId($deviceId)
    {
        return $this->getByFields(['device_id' => $deviceId]);
    }
}
