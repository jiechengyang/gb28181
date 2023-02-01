<?php

namespace Biz\PlayRecord\Exception;

use support\exception\AbstractException;

class PlayRecordException extends AbstractException 
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
