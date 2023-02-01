<?php


namespace Biz\Queue\Service;


use Codeages\Biz\Framework\Queue\Job;

interface QueueService
{
    public function pushJob(Job $job, $queue = null);
}