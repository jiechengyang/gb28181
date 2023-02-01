<?php

namespace Biz\VideoChannels\Dao\Impl;

use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;
use Biz\VideoChannels\Dao\VideoChannelsDao;

class VideoChannelsDaoImpl extends AdvancedDaoImpl implements VideoChannelsDao
{

    protected $table = 'smp_video_channels';

    public function declares()
    {
        return [
            'serializes' => [
            ],
            'orderbys' => [
                'id',
                'enabled',
                'locked',
                'device_status',
                'lastOnlineTime',
                'createdTime',
                'updatedTime',
            ],
            'conditions' => [
                'id =: id',
                'id > :id_GT',
                'id IN ( :ids)',
                'id NOT IN ( :noIds)',
                'device_status = :deviceStatus',
                'lastOnlineTime >= :lastOnlineTime_GE',
                'lastOnlineTime <= :lastOnlineTime_LE',
                'recorder_id = :recorderId',
                'recorder_id <> :noRecorderId',
                'recorder_id IN (:recorderIds)',
                'record_status = :recordStatus',
                'parter_id = :parterId',
                'parter_id = :partnerId',
                'parter_id != :noParterId',
                'parter_id != :noPartnerId',
                'record_plan_id = :recordPlanId',
                'record_plan_id <>:noRecordPlanId',
                'record_plan_id IN (:planIds)',
                'parter_id IN (:parterIds)',
                'parter_id IN (:partnerIds)',
                'device_id = :deviceId',
                'channel_id = :channelId',
                'rtp_proto = :rtpProto',
                'enabled = :enabled',
                'main_id = :mainId',
                'main_id IN (:mainIds)',
                'media_server_id = :mediaServerId',
                'channel_name LIKE :channelName_LIKE',
                'channel_name = :channelName',
                'createdTime >= :startTime',
                'createdTime <= :endTime',
                'enabled = :enabled',
                'locked = :locked',
                'auto_live = :autoLive',
                '( device_id LIKE :keywordsLike OR channel_id LIKE :keywordsLike OR media_server_id LIKE :keywordsLike OR main_id LIKE :keywordsLike OR app LIKE :keywordsLike OR channel_name LIKE :keywordsLike OR ip_v4_address LIKE :keywordsLike OR local_ip_v4 LIKE :keywordsLike)'
            ],
            'timestamps' => [
                'createdTime',
                'updatedTime',
            ],
        ];
    }

    public function findByDeviceId($deviceId)
    {
        return $this->getByFields(['device_id' => $deviceId]);
    }

    public function findByDeviceIdAndChannelId($deviceId, $channelId)
    {
        return $this->getByFields(['device_id' => $deviceId, 'channel_id' => $channelId]);
    }

    public function findByChannelId($channelId)
    {
        return $this->getByFields(['channel_id' => $channelId]);
    }

    public function findByMainId($mainId)
    {
        return $this->getByFields(['main_id' => $mainId]);
    }

    public function findInByIds($ids)
    {
        return $this->findInField('id', $ids);
    }
}
