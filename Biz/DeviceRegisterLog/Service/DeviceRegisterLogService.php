<?php


namespace Biz\DeviceRegisterLog\Service;


interface DeviceRegisterLogService
{
    public function createRegisterLog($fields);

    public function countLogs(array $conditions = []);

    public function searchLogs(array $conditions = [], array $orderby = [], $start, $limit, array $columns = []);
}