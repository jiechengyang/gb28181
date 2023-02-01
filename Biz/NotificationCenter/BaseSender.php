<?php


namespace Biz\NotificationCenter;


use support\Singleton;

abstract class BaseSender
{
    use Singleton;

    protected $afterSendInfo = [];
}