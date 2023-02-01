<?php


namespace app\admin\filters;


use app\admin\helpers\AssetHelper;
use Biz\DataFilters\Filter;

class UserFilter extends Filter
{
    protected $simpleFields = [
        'id', 'nickname', 'email', 'smallAvatar', 'mediumAvatar', 'largeAvatar', 'uuid', 'destroyed', 'roles', 'currentIp', 'verifiedMobile', 'loginTime', 'createdTime', 'loginIp', 'truename','locked'
    ];

    protected $publicFields = [
        'about', 'faceRegistered',
    ];

    protected $authenticatedFields = [
        'email', 'locale', 'uri', 'type', 'roles', 'promotedSeq', 'locked', 'currentIp', 'gender', 'iam', 'city', 'company', 'verifiedMobile', 'promotedTime', 'loginTime', 'approvalTime'
    ];

    protected $mode = self::SIMPLE_MODE;

    protected function simpleFields(&$data)
    {
        !empty($data['loginTime']) && $data['loginTime'] = date('c', $data['loginTime']);

        $this->transformAvatar($data);
        $this->destroyedNicknameFilter($data);
    }

    protected function publicFields(&$data)
    {
        if (!isset($data['about'])) {
            return;
        }
        $data['about'] = $this->convertAbsoluteUrl($data['about']);
    }

    protected function authenticatedFields(&$data)
    {
        $data['loginTime'] = date('c', $data['loginTime']);
        $data['email'] = '*****';
        if (!empty($data['verifiedMobile'])) {
            $data['verifiedMobile'] = substr_replace($data['verifiedMobile'], '****', 3, 4);
        } else {
            unset($data['verifiedMobile']);
        }
    }

    private function transformAvatar(&$data)
    {
        $data['smallAvatar'] = AssetHelper::getFurl($data['smallAvatar'], 'avatar.png');
        $data['mediumAvatar'] = AssetHelper::getFurl($data['mediumAvatar'], 'avatar.png');
        $data['largeAvatar'] = AssetHelper::getFurl($data['largeAvatar'], 'avatar.png');
        $data['avatar'] = [
            'small' => $data['smallAvatar'],
            'middle' => $data['mediumAvatar'],
            'large' => $data['largeAvatar'],
        ];

        unset($data['smallAvatar']);
        unset($data['mediumAvatar']);
        unset($data['largeAvatar']);
    }

    protected function destroyedNicknameFilter(&$data)
    {
        $data['nickname'] = (1 == $data['destroyed']) ? '帐号已注销' : $data['nickname'];
    }
}