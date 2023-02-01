<?php


namespace  Biz\NotificationCenter;


interface SenderInterface
{
    public function sendMessage($message, array $params = []);

    /**
     * @return string|array|bool|int
     */
    public function getAfterSendInfo();
}