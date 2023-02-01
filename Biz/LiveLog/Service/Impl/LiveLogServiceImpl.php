<?php

namespace Biz\LiveLog\Service\Impl;

use Biz\BaseService;

use Biz\LiveLog\Service\LiveLogService;
use Biz\LiveLog\Dao\LiveLogDao;
use support\utils\ArrayToolkit;

class LiveLogServiceImpl extends BaseService implements LiveLogService 
{
    public function getLiveLogById($id)
    {
        return $this->getLiveLogDao()->get($id);
    }

    public function createLiveLog(array $fields)
    {
        $fields = ArrayToolkit::parts($fields, ['status', 'parter_key', 'live_provider', 'url', 'expireTime', 'request_ip', 'request_params', 'response_content', 'error_message']);

        return $this->getLiveLogDao()->create($fields);
    }

    public function updateLiveLog($id, array $fields)
    {
    
    }

    public function deleteLiveLogById($id)
    {
    
    }

    /**
      * @return LiveLogDao
      */
    protected function getLiveLogDao()
    {
        return $this->createDao('LiveLog:LiveLogDao');
    
    }

}
