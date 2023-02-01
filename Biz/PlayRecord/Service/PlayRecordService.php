<?php

namespace Biz\PlayRecord\Service;

interface PlayRecordService
{
    public function getPlayRecordById($id);

    public function createPlayRecord(array $fields);

    public function updatePlayRecord($id, array $fields);

    public function deletePlayRecordById($id);

    public function countRecords(array $conditions);

    public function searchRecords(array $conditions, array $orderby = [], $start, $limit, array  $columns = []);

}
