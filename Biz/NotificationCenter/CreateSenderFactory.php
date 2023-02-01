<?php


namespace  Biz\NotificationCenter;

use ReflectionClass;

class CreateSenderFactory
{

    /**
     * @var SenderInterface
     */
    private $sender;

    private $senderClassMap = [
        'DingDing' => DingDingSender::class,
        'Email' => MailSender::class,
    ];

    private $regiseterMsgSenders = [];

    public function __construct(string $mode)
    {
        if (!isset($this->senderClassMap[$mode])) {
            throw new \Exception("{$mode} is not SenderInterface");
        }

        $this->sender = $this->createSender($mode);

    }


    /**
     * @param $mode
     * @return mixed|object|SenderInterface
     * @throws \ReflectionException
     */
    protected function createSender($mode)
    {
        if (isset($this->regiseterMsgSenders[$mode])) {
            $strategyMode = $this->regiseterMsgSenders[$mode];
        } else {
            $reflection = new ReflectionClass($this->senderClassMap[$mode]);
            $strategyMode = $reflection->newInstanceArgs();
            $this->regiseterMsgSenders[] = $strategyMode;
        }

        return $strategyMode;
    }

    public function send($message, array $params = [])
    {
        return $this->sender->sendMessage($message, $params);
    }

    public function getAfterSendInfo()
    {
        return $this->sender->getAfterSendInfo();
    }

    public function sendAll($message, array $params = [])
    {
        foreach ($this->senderClassMap as $mode) {
            $sender = $this->createSender($mode);
            $sender->sendMessage($message, $params);
        }
    }
}