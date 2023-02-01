<?php


namespace Biz\User\Register\Impl;


use support\utils\SimpleValidator;
use Biz\User\Exception\UserException;

class MobileRegDecoderImpl extends RegDecoder
{

    protected function validateBeforeSave($registration)
    {
        if (!empty($registration['mobile']) && !SimpleValidator::mobile($registration['mobile'])) {
            throw UserException::MOBILE_INVALID();
        }

        if (!$this->getUserService()->isMobileAvaliable($registration['mobile'])) {
            throw UserException::MOBILE_EXISTED();
        }
    }

    protected function dealDataBeforeSave($registration, $user)
    {
        if (empty($registration['email'])) {
            $user['email'] = $this->getUserService()->generateEmail($registration);
        }

        return $user;
    }
}