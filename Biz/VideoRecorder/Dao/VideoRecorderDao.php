<?php

namespace Biz\VideoRecorder\Dao;

use Codeages\Biz\Framework\Dao\AdvancedDaoInterface;

interface VideoRecorderDao extends AdvancedDaoInterface
{
    /**
     * @param $deviceId
     * @return array|null
     */
    public function findByDeviceId($deviceId);
}
