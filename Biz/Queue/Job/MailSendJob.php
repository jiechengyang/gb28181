<?php

namespace Biz\Queue\Job;

use Biz\Queue\BaseJob;
use Webman\RedisQueue\Consumer;

class MailSendJob extends BaseJob implements Consumer
{
    public $queue = 'send:mail';

    public $connection = 'default';

    public function consume($data)
    {
        // var_export($data);
    }
}
