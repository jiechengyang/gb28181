<?php


namespace Biz\DeviceActiveLog\Service;


interface DeviceActiveLogService
{
    public function createActiveLog($fields);

    public function getActiveLogsByDeviceId($deviceId);

    public function countLogs(array $conditions = []);

    public function searchLogs(array $conditions = [], array $orderby, $start, $limit, array $columns = []);
}