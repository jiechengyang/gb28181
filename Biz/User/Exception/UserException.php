<?php


namespace Biz\User\Exception;

use support\exception\AbstractException;

class UserException extends AbstractException
{
    const UN_LOGIN = 4010101;
    const NOTFOUND_USER = 4220104;

    const PASSWORD_ERROR = 4220116;
    const PASSWORD_FAILED = 4220117;

    const NOTFOUND_TOKEN = 4010118;

    const CAPTCHA_ERROR = 4220119;
    const CAPTCHA_EMPTY = 4220120;
    const USERNAME_PASSWORD_ERROR = 4220121;

    const TOKEN_PARAMS_FAILED = 5000219;

    const LOCKED_USER = 4030115;

    const LOCK_DENIED = 4030140;

    const ROLES_INVALID = 5000135;

    const EMAIL_INVALID = 5000119;

    const EMAIL_EXISTED = 5000120;

    const MOBILE_INVALID = 5000121;

    const MOBILE_EXISTED = 5000122;

    const NICKNAME_INVALID = 5000112;

    const NICKNAME_EXISTED = 5000113;

    const TRUENAME_INVALID = 5000139;

    const PASSWORD_INVALID = 5000123;

    public function __construct($code)
    {
        $this->setMessages();

        parent::__construct($code);
    }

    public function setMessages()
    {
        $this->messages = [
            self::UN_LOGIN => '用户未登录',
            self::NOTFOUND_USER => '该用户不存在',
            self::PASSWORD_ERROR => '用户名或密码错误',
            self::PASSWORD_FAILED => '用户名或密码错误',
            self::NOTFOUND_TOKEN => 'token不存在',
            self::LOCKED_USER => '账户被封禁，请联系管理员',
            self::ROLES_INVALID => '角色不正确',
            self::EMAIL_INVALID => '邮箱地址格式错误',
            self::EMAIL_EXISTED => '邮箱地址已被注册',
            self::MOBILE_INVALID => '非法的手机号',
            self::MOBILE_EXISTED => '手机号已被注册',
            self::NICKNAME_INVALID => '昵称格式错误',
            self::NICKNAME_EXISTED => '昵称已经存在',
            self::TRUENAME_INVALID => '真实姓名错误',
            self::PASSWORD_INVALID => '密码校验失败',
            self::CAPTCHA_ERROR => '验证码输入错误',
            self::CAPTCHA_EMPTY => '验证码不能为空',
            self::USERNAME_PASSWORD_ERROR => '用户名或密码不能为空',
            self::LOCK_DENIED => '没有封禁该角色的权限',
        ];
    }
}