<?php

namespace Biz\LiveLog\Service;

interface LiveLogService
{
    public function getLiveLogById($id);

    public function createLiveLog(array $fields);

    public function updateLiveLog($id, array $fields);

    public function deleteLiveLogById($id);

}
