<?php

namespace Biz\VideoChannels\Dao;

use Codeages\Biz\Framework\Dao\AdvancedDaoInterface;

interface VideoChannelsDao extends AdvancedDaoInterface
{
    public function findByDeviceId($deviceId);

    public function findByChannelId($channelId);

    public function findByMainId($mainId);

    public function findByDeviceIdAndChannelId($deviceId, $channelId);

    public function findInByIds($ids);
}
