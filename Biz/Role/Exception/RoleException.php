<?php

namespace Biz\Role\Exception;

use support\exception\AbstractException;

class RoleException extends AbstractException 
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
