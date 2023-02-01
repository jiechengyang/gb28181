<?php


namespace Biz\Queue\Driver;


use Codeages\Biz\Framework\Queue\Driver\AbstractQueue;
use Codeages\Biz\Framework\Queue\Driver\Queue;
use Codeages\Biz\Framework\Queue\Job;
use Webman\RedisQueue\Client;

class RedisQueue extends AbstractQueue implements Queue
{
    public function push(Job $job)
    {
        $delay = $job->getMetadata('delay', 0);
        $jobRecord = [
            'queue' => $this->name,
            'body' => serialize($job->getBody()),
            'class' => get_class($job),
            'timeout' => $job->getMetadata('timeout', Job::DEFAULT_TIMEOUT),
            'priority' => $job->getMetadata('priority', Job::DEFAULT_PRIORITY),
            'available_time' => $delay,
        ];

        Client::send($this->name, $jobRecord);
    }

    public function pop(array $options = array())
    {
        Client::pull();
    }

    public function delete(Job $job)
    {
        return true;
    }

    public function release(Job $job)
    {
        return true;
    }
}