<?php


namespace Biz\LiveProvider\Strategy;


use Biz\Queue\Service\QueueService;
use Biz\SystemLog\Service\SystemLogService;
use Codeages\Biz\Framework\Context\Biz;

class LiveProviderStrategy
{
    protected $biz;

    public function __construct(Biz $biz)
    {
        $this->biz = $biz;
    }

    public function __destruct()
    {
        $this->biz = null;
    }

    /**
     *
     * 获取视频截图
     * @param $code
     * @param $protocol
     * @return string|null
     */
    public function getVideoCover($code, $protocol = '')
    {
    }

    public function activeAndOpenLiveWithCameras(array $conditions, $sort, $offset, $limit, $options = [])
    {

    }

    public function openLiveWithCameras(array $conditions, array $options = [])
    {

    }

    public function deviceTrees(array $conditions = [])
    {

    }

    public function countVideoChannels(array $conditions)
    {

    }

    public function countRecorders(array $conditions)
    {

    }

    public function searchRecorders(array $conditions, $sort, $offset, $limit, $columns = [])
    {

    }

    public function searchCameras(array $conditions, $sort, $offset, $limit, $columns = [])
    {

    }

    /**
     * @return QueueService
     */
    protected function getQueueService()
    {
        return $this->createService('Queue:QueueService');
    }

    /**
     * @return \Biz\Setting\Service\SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('Setting:SettingService');
    }

    /**
     * @return SystemLogService
     */
    protected function getSystemLogService()
    {
        return $this->createService('SystemLog:SystemLogService');
    }

    protected function createService($alias)
    {
        return $this->biz->service($alias);
    }

    protected function createDao($alias)
    {
        return $this->biz->service($alias);
    }
}