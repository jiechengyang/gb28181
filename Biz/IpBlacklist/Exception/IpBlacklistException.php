<?php

namespace Biz\IpBlacklist\Exception;

use support\exception\AbstractException;

class IpBlacklistException extends AbstractException 
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
