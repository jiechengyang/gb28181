<?php

namespace Biz\PlayRecord\Service\Impl;

use Biz\BaseService;

use Biz\PlayRecord\Service\PlayRecordService;
use Biz\PlayRecord\Dao\PlayRecordDao;
use support\utils\ArrayToolkit;

class PlayRecordServiceImpl extends BaseService implements PlayRecordService
{
    public function countRecords(array $conditions)
    {
        return $this->getPlayRecordDao()->count($conditions);    
    }

    public function searchRecords(array $conditions, array $orderby = [], $start, $limit, array $columns = [])
    {
        return $this->getPlayRecordDao()->search($conditions, $orderby, $start, $limit, $columns);
    }

    public function getPlayRecordById($id)
    {
        return $this->getPlayRecordDao()->get($id);
    }

    public function createPlayRecord(array $fields)
    {
        $fields = ArrayToolkit::parts($fields, ['vc_id', 'code', 'media_server_id', 'client_ip', 'player_id', 'server_port', 'params', 'startTime']);

        return $this->getPlayRecordDao()->create($fields);
    }

    public function updatePlayRecord($id, array $fields)
    {

    }

    public function deletePlayRecordById($id)
    {

    }

    /**
     * @return PlayRecordDao
     */
    protected function getPlayRecordDao()
    {
        return $this->createDao('PlayRecord:PlayRecordDao');

    }

}
