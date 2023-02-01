<?php

namespace Biz\Setting\Exception;

use support\exception\AbstractException;

class SettingException extends AbstractException 
{
    const AK_CONFIG_API_EMPTY = 5000900;

    const AK_CONFIG_AK_EMPTY  = 5000901;

    public function __construct($code)
    {
        $this->setMessages();
        parent::__construct($code);
    }

    public function setMessages()
    {
        $this->messages = [
            self::AK_CONFIG_API_EMPTY => '服务网关api url不能为空',
            self::AK_CONFIG_AK_EMPTY => '服务网关 ak 不能为空',
        ];
    }

}
