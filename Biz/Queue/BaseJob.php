<?php


namespace Biz\Queue;


use Biz\SystemLog\Service\SystemLogService;
use Codeages\Biz\Framework\Context\Biz;
use support\bootstrap\BizInit;
use support\bootstrap\Container;

class BaseJob
{
    /**
     *
     * @return Biz
     */
    final protected function getBiz()
    {
        return BizInit::init();
//        return Container::get(Biz::class);
    }

    /**
     * @return SystemLogService
     */
    protected function getLogService()
    {
        return $this->createService('SystemLog:SystemLogService');
    }

    protected function createService($serviceAlias)
    {
        return $this->getBiz()->service($serviceAlias);
    }
}