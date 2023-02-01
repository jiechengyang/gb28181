<?php


namespace  Biz\NotificationCenter;


class MailSender extends BaseSender implements SenderInterface
{
    public function sendMessage($message, array $params = [])
    {
        echo '邮件发送', PHP_EOL, '内容：', $message;
        sleep(1);
        echo PHP_EOL,'发送成功-------------',PHP_EOL;
    }

    /**
     * @return string|array|bool|int
     */
    public function getAfterSendInfo()
    {
    }
}
