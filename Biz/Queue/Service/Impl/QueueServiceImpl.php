<?php


namespace Biz\Queue\Service\Impl;


use Biz\Queue\Service\QueueService;
use Codeages\Biz\Framework\Queue\Job;

class QueueServiceImpl extends \Codeages\Biz\Framework\Queue\Service\Impl\QueueServiceImpl implements QueueService
{
    public function pushJob(Job $job, $queue = 'redis')
    {
        $queueName = empty($queue) ? 'redis' : (string)$queue;
        $queue = $this->biz['queue.connection.' . $queueName];
        $queue->push($job);
    }
}