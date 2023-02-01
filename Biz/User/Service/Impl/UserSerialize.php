<?php

namespace Biz\User\Service\Impl;

class UserSerialize
{
    public static function serialize(array $user)
    {
        return $user;
    }

    public static function unserialize(array $user = null)
    {
        if (empty($user)) {
            return null;
        }

        $user = self::_userRolesSort($user);

        return $user;
    }

    public static function unserializes(array $users)
    {
        return array_map(function ($user) {
            return UserSerialize::unserialize($user);
        }, $users);
    }

    private static function _userRolesSort($user)
    {
        if (!empty($user['roles'][1]) && 'ROLE_PARTER_ADMIN' == $user['roles'][1]) {
            $temp = $user['roles'][1];
            $user['roles'][1] = $user['roles'][0];
            $user['roles'][0] = $temp;
        }

        return $user;
    }
}