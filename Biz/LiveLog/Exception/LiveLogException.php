<?php

namespace Biz\LiveLog\Exception;

use support\exception\AbstractException;

class LiveLogException extends AbstractException 
{
    public function __construct($code)
    {
        $this->setMessages();
        parent::__construct($code);
    }

    public function setMessages()
    {
        $this->messages = [
        
        ];
    }

}
