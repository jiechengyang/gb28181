<?php


namespace Biz\DeviceRegisterLog\Event;


use Codeages\Biz\Framework\Event\Event;
use Codeages\Biz\Framework\Event\EventSubscriber;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DeviceRegisterSubscriber extends EventSubscriber implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            'device.register' => 'onRegister',
            'device.unRegister' => 'onUnRegister',
        ];
    }

    public function onRegister(Event $event)
    {

    }

    public function onUnRegister(Event $event)
    {

    }
}