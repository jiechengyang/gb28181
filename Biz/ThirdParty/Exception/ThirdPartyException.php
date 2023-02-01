<?php


namespace Biz\ThirdParty\Exception;


use support\exception\AbstractException;

class ThirdPartyException extends AbstractException
{
    const ALREADY_EXIST_PARTNER_NAME = 5001401;
    const ALREADY_EXIST_PARTNER_KEY = 5001402;
    const ALREADY_EXIST_PARTNER_SCERET = 5001403;
    const NOTFOUND_PARTNER = 4041001;

    const FAILED_APP_KEY = 4031401;
    const LOCKED_PARTNER = 4031402;
    const EXPIRED_PARTNER = 4031403;
    const LIVE_PROVINDER_NOT_EXIST = 4031404;

    public function __construct($code)
    {
        $this->setMessages();

        parent::__construct($code);
    }

    public function setMessages()
    {
        $this->messages = [
            self::ALREADY_EXIST_PARTNER_NAME => '合作方名称已被使用',
            self::ALREADY_EXIST_PARTNER_KEY => '合作方APP KEY已存在',
            self::ALREADY_EXIST_PARTNER_SCERET => '合作方APP SCERET已存在',
            self::NOTFOUND_PARTNER => '合作方不存在',
            self::FAILED_APP_KEY => '合作方APP KEY 参数不正确',
            self::LOCKED_PARTNER => '合作方已被锁定，请联系管理人员解封',
            self::EXPIRED_PARTNER => '合作方开放api使用期限已到，请联系管理人员续费',
            self::LIVE_PROVINDER_NOT_EXIST => '没有该平台的接入权限'
        ];
    }
}