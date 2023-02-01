<?php

namespace Biz\User\Service\Impl;

use Biz\Constants;
use Biz\IpBlacklist\Service\IpBlacklistService;
use Biz\Role\Service\RoleService;
use Biz\SystemLog\Service\SystemLogService;
use Biz\User\Service\AuthService;
use Codeages\Biz\Framework\Event\Event;
use support\utils\ArrayToolkit;
use support\utils\FileToolkit;
use support\utils\SimpleValidator;
use support\utils\StringToolkit;
use Biz\BaseService;
use Biz\Common\CommonException;
use Biz\SystemLog\Service\SystemLogService as LogService;
use Biz\Setting\Service\SettingService;
use Biz\Setting\Exception\SettingException;
use Biz\User\Dao\TokenDao;
use Biz\User\Dao\UserBindDao;
use Biz\User\Dao\UserProfileDao;
use Biz\User\Service\UserService;
use Biz\User\Dao\UserDao;
use Biz\User\Exception\UserException;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Security\Core\Encoder\MessageDigestPasswordEncoder;
use support\ServiceKernel;

class UserServiceImpl extends BaseService implements UserService
{
    public function getUser($id, $lock = false)
    {
        $user = $this->getUserDao()->get($id, ['lock' => $lock]);

        return !$user ? null : UserSerialize::unserialize($user);
    }

    public function getUserAndProfile($id)
    {
        $user = $this->getUserDao()->get($id);

        if (!empty($user)) {
            $profile = $this->getProfileDao()->get($id);
            $user = array_merge($user, $profile);
        }

        return $user;
    }

    public function countUsers(array $conditions)
    {
        if (isset($conditions['nickname'])) {
            $conditions['nickname'] = strtoupper($conditions['nickname']);
        }

        return $this->getUserDao()->count($conditions);
    }

    public function searchUsers(array $conditions, array $orderBy, $start, $limit, $columns = [])
    {
        if (isset($conditions['nickname'])) {
            $conditions['nickname'] = strtoupper($conditions['nickname']);
        }

        $users = $this->getUserDao()->search($conditions, $orderBy, $start, $limit, $columns);

        return UserSerialize::unserializes($users);
    }

    public function updateUserForDestroyedAccount($userId, $destroyedId)
    {
        $user = $this->getUser($userId);
        $userFields = [
            'nickname' => '注销ID_' . $destroyedId,
            'email' => $this->generateEmail($user),
            'emailVerified' => 0,
            'verifiedMobile' => '',
            'smallAvatar' => '',
            'mediumAvatar' => '',
            'largeAvatar' => '',
            'destroyed' => 1,
        ];

        $userProfile = [
            'idcard' => '',
            'mobile' => '',
        ];
        $this->getProfileDao()->update($userId, $userProfile);
        $this->changeUserRoles($userId, ['ROLE_PARTER_ADMIN']);

        return $this->getUserDao()->update($userId, $userFields);
    }

    public function deleteUserBindByUserId($userId)
    {
        return $this->getUserBindDao()->deleteByToId($userId);
    }

    public function searchUserProfileCount(array $conditions)
    {
        return $this->getProfileDao()->count($conditions);
    }

    public function searchTokenCount($conditions)
    {
        return $this->getUserTokenDao()->count($conditions);
    }

    public function findUserLikeNickname($nickname)
    {
        return $this->getUserDao()->findUserLikeNickname($nickname);
    }

    public function findUserLikeTruename($truename)
    {
        return $this->getProfileDao()->findUserLikeTruename($truename);
    }

    public function getSimpleUser($id)
    {
        $user = $this->getUser($id);

        $simple = [];

        $simple['id'] = $user['id'];
        $simple['nickname'] = $user['nickname'];
        $simple['title'] = $user['title'];
        $simple['avatar'] = '';//use Biz\Content\FileException;


        return $simple;
    }

    public function countUsersByLessThanCreatedTime($endTime)
    {
        return $this->getUserDao()->countByLessThanCreatedTime($endTime);
    }

    public function getUserProfile($id)
    {
        return $this->getProfileDao()->get($id);
    }

    public function getUserByNickname($nickname)
    {
        $user = $this->getUserDao()->getByNickname($nickname);

        return !$user ? null : UserSerialize::unserialize($user);
    }

    public function getUnDstroyedUserByNickname($nickname)
    {
        $user = $this->getUserDao()->getUnDestroyedUserByNickname($nickname);

        return !$user ? null : UserSerialize::unserialize($user);
    }

    public function getUnDstroyedUserByNickNameOrVerifiedMobile($value)
    {
        $user = $this->getUserDao()->getUnDstroyedUserByNickNameOrVerifiedMobile($value);

        return !$user ? null : UserSerialize::unserialize($user);
    }

    public function getUserByLoginField($keyword, $isFilterDestroyed = false)
    {
        if (SimpleValidator::email($keyword)) {
            $user = $this->getUserDao()->getByEmail($keyword);
        } elseif (SimpleValidator::mobile($keyword)) {
            $user = $this->getUserDao()->getByVerifiedMobile($keyword);
        } else {
            $user = $this->getUserDao()->getByNickname($keyword);
        }

        if (isset($user['type']) && 'system' == $user['type']) {
            return null;
        }

        if ($isFilterDestroyed && 1 == $user['destroyed']) {
            return null;
        }

        return !$user ? null : UserSerialize::unserialize($user);
    }

    public function getUserByVerifiedMobile($mobile)
    {
        if (empty($mobile)) {
            return null;
        }
        $user = $this->getUserDao()->getByVerifiedMobile($mobile);

        return !$user ? null : UserSerialize::unserialize($user);
    }

    public function countUsersByMobileNotEmpty()
    {
        return $this->getUserDao()->countByMobileNotEmpty();
    }

    public function countUserHasMobile($needVerified = false)
    {
        if ($needVerified) {
            $count = $this->countUsers([
                'locked' => 0,
                'hasVerifiedMobile' => true,
            ]);
        } else {
            $count = $this->countUsersByMobileNotEmpty();
        }

        return $count;
    }

    public function findUsersHasMobile($start, $limit, $needVerified = false)
    {
        $conditions = [
            'locked' => 0,
        ];
        $orderBy = ['createdTime' => 'ASC'];
        if ($needVerified) {
            $conditions['hasVerifiedMobile'] = true;
            $users = $this->searchUsers($conditions, $orderBy, $start, $limit);
        } else {
            $users = $this->getUserDao()->findUnlockedUsersWithMobile($start, $limit);
        }

        return $users;
    }

    public function getUserByEmail($email)
    {
        if (empty($email)) {
            return null;
        }

        $user = $this->getUserDao()->getByEmail($email);

        return !$user ? null : UserSerialize::unserialize($user);
    }

    public function findUsersByIds(array $ids)
    {
        $users = UserSerialize::unserializes(
            $this->getUserDao()->findByIds($ids)
        );

        return ArrayToolkit::index($users, 'id');
    }

    public function findUnDestroyedUsersByIds($ids)
    {
        $users = UserSerialize::unserializes(
            $this->getUserDao()->findUnDestroyedUsersByIds($ids)
        );

        return ArrayToolkit::index($users, 'id');
    }

    public function findUserProfilesByIds(array $ids)
    {
        $userProfiles = $this->getProfileDao()->findByIds($ids);

        return ArrayToolkit::index($userProfiles, 'id');
    }

    public function searchUserProfiles(array $conditions, array $orderBy, $start, $limit, $columns = [])
    {
        $profiles = $this->getProfileDao()->search($conditions, $orderBy, $start, $limit, $columns);

        return $profiles;
    }

    public function setEmailVerified($userId)
    {
        $this->getUserDao()->update($userId, ['emailVerified' => 1]);
        $user = $this->getUser($userId);
        $this->dispatchEvent('email.verify', new Event($user));
    }

    public function changeNickname($userId, $nickname)
    {
        $user = $this->getUser($userId);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        if (!SimpleValidator::nickname($nickname)) {
            $this->createNewException(UserException::NICKNAME_INVALID());
        }

        $existUser = $this->getUserDao()->getByNickname($nickname);

        if ($existUser && $existUser['id'] != $userId) {
            $this->createNewException(UserException::NICKNAME_EXISTED());
        }

        $updatedUser = $this->getUserDao()->update($userId, ['nickname' => $nickname]);
        $this->dispatchEvent('user.change_nickname', new Event($updatedUser));
    }

    public function changeEmail($userId, $email)
    {
        if (!SimpleValidator::email($email)) {
            $this->createNewException(UserException::EMAIL_INVALID());
        }

        $user = $this->getUserDao()->getByEmail($email);

        if ($user && $user['id'] != $userId) {
            $this->createNewException(UserException::EMAIL_EXISTED());
        }

        $updatedUser = $this->getUserDao()->update($userId, ['email' => $email]);
        $this->dispatchEvent('user.change_email', new Event($updatedUser));

        return $updatedUser;
    }


    public function updateUserUpdatedTime($id)
    {
        return $this->getUserDao()->update($id, []);
    }


    public function isNicknameAvaliable($nickname)
    {
        if (empty($nickname)) {
            return false;
        }

        $user = $this->getUserDao()->getByNickname($nickname);

        return empty($user) ? true : false;
    }

    public function isEmailAvaliable($email)
    {
        if (empty($email)) {
            return false;
        }

        $user = $this->getUserDao()->getByEmail($email);

        return empty($user) ? true : false;
    }

    public function isMobileAvaliable($mobile)
    {
        if (empty($mobile)) {
            return false;
        }

        $user = $this->getUserDao()->getByVerifiedMobile($mobile);

        return empty($user) ? true : false;
    }

    public function changePassword($id, $password)
    {
        if (empty($password)) {
            $this->createNewException(CommonException::ERROR_PARAMETER());
        }

        if (!$this->validatePassword($password)) {
            $this->createNewException(UserException::PASSWORD_INVALID());
        }

        $user = $this->getUser($id);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        $salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);

        $fields = [
            'salt' => $salt,
            'password' => $this->getPasswordEncoder()->encodePassword($password, $salt),
        ];

        $this->getUserDao()->update($id, $fields);

        $this->refreshLoginSecurityFields($user['id'], $this->getCurrentUser()->currentIp);

        $this->dispatch('user.change_password', $user);

        return true;
    }

    public function isMobileUnique($mobile)
    {
        $count = $this->countUsers(['wholeVerifiedMobile' => $mobile]);

        if ($count > 0) {
            return false;
        }

        return true;
    }

    public function changeMobile($id, $mobile)
    {
        if (empty($mobile)) {
            $this->createNewException(CommonException::ERROR_PARAMETER());
        }

        $user = $this->getUser($id);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        $userGetByMobile = $this->getUserDao()->getByVerifiedMobile($mobile);
        if ($userGetByMobile && $userGetByMobile['id'] !== $user['id']) {
            $this->createNewException(UserException::MOBILE_EXISTED());
        }

        $fields = [
            'verifiedMobile' => $mobile,
        ];

        $this->getUserDao()->update($id, $fields);
        $this->updateUserProfile($id, [
            'mobile' => $mobile,
        ]);

        $this->dispatchEvent('user.change_mobile', new Event($user));

        return true;
    }

    public function verifyInSaltOut($in, $salt, $out)
    {
        return $out == $this->getPasswordEncoder()->encodePassword($in, $salt);
    }

    public function verifyPassword($id, $password)
    {
        $user = $this->getUser($id);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        return $this->verifyInSaltOut($password, $user['salt'], $user['password']);
    }

    public function verifyPayPassword($id, $payPassword)
    {
        $user = $this->getUser($id);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        return $this->verifyInSaltOut($payPassword, $user['payPasswordSalt'], $user['payPassword']);
    }


    public function initSystemUsers()
    {
        $users = [
            [
                'type' => 'system',
                'roles' => ['ROLE_SUPER_ADMIN'],
            ],
        ];
        foreach ($users as $user) {
            $existsUser = $this->getUserDao()->getUserByType($user['type']);

            if (!empty($existsUser)) {
                continue;
            }

            $user['nickname'] = $this->generateNickname($user) . '(系统用户)';
            $user['emailVerified'] = 1;
            $user['orgId'] = 1;
            $user['orgCode'] = '1.';
            $user['password'] = $this->getRandomChar();
            $user['email'] = $this->generateEmail($user);
            $user['salt'] = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
            $user['password'] = $this->getPasswordEncoder()->encodePassword($user['password'], $user['salt']);
            $user = UserSerialize::unserialize(
                $this->getUserDao()->create(UserSerialize::serialize($user))
            );

            $profile = [];
            $profile['id'] = $user['id'];
            $this->getProfileDao()->create($profile);
        }
    }

    public function getUserByType($type)
    {
        return $this->getUserDao()->getUserByType($type);
    }

    public function getUserByUUID($uuid)
    {
        return $this->getUserDao()->getByUUID($uuid);
    }

    /**
     * @registration type属性使用了原先的 $type 参数, 不填，则为default （原先的接口参数为 $registration, $type)
     *
     * @param $registerTypes 数组，可以是多个类型的组合
     *   类型范围  email, mobile, binder(第三方登录)
     */
    public function register($registration, $registerTypes = ['email'])
    {
        $register = $this->biz['user.register']->createRegister($registerTypes);

        list($user, $inviteUser) = $register->register($registration);

        if (!empty($inviteUser)) {
            $this->dispatchEvent(
                'user.register',
                new Event(['userId' => $user['id'], 'inviteUserId' => $inviteUser['id']])
            );
        }

        $this->dispatchEvent('user.registered', new Event($user));

        return $user;
    }

    public function generateNickname($registration, $maxLoop = 100)
    {
        $rawNickname = $registration['nickname'] ?? '';
        if (!empty($rawNickname)) {
            $rawNickname = preg_replace('/[^\x{4e00}-\x{9fa5}a-zA-z0-9_.]+/u', '', $rawNickname);
            $rawNickname = str_replace(['-'], ['_'], $rawNickname);

            if (!SimpleValidator::nickname($rawNickname)) {
                $rawNickname = '';
            }
            if ($this->isNicknameAvaliable($rawNickname)) {
                return $rawNickname;
            }
        }

        if (empty($rawNickname)) {
            $rawNickname = 'user';
        }
        $rawLen = (strlen($rawNickname) + mb_strlen($rawNickname, 'utf-8')) / 2;
        if ($rawLen > 12) {
            $rawNickname = substr($rawNickname, 0, -6);
        }
        for ($i = 0; $i < $maxLoop; ++$i) {
            $nickname = $rawNickname . substr($this->getRandomChar(), 0, 6);

            if ($this->isNicknameAvaliable($nickname)) {
                break;
            }
        }

        return $nickname;
    }

    public function generateEmail($registration, $maxLoop = 100)
    {
        for ($i = 0; $i < $maxLoop; ++$i) {
            $registration['email'] = 'user_' . substr($this->getRandomChar(), 0, 9) . '@boyuntong.net';

            if ($this->isEmailAvaliable($registration['email'])) {
                break;
            }
        }

        return $registration['email'];
    }

    public function importUpdateEmail($users)
    {
        $this->beginTransaction();
        try {
            for ($i = 0, $iMax = count($users); $i < $iMax; ++$i) {
                $member = $this->getUserDao()->getByEmail($users[$i]['email']);
                $member = UserSerialize::unserialize($member);
                $this->changePassword($member['id'], trim($users[$i]['password']));
                $this->updateUserProfile($member['id'], $users[$i]);
            }

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function updateUserAndUserProfileWithAdmin($id, $fields)
    {
        $user = $this->getUser($id);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        try {
            $this->beginTransaction();
            if (!empty($fields['password'])) {
                if (!SimpleValidator::password($fields['password'])) {
                    throw $this->createInvalidArgumentException('密码校验失败');
                }
                list($salt, $passwordHash) = $this->makePasswordHashAndSalt($fields['password']);
                $fields['salt'] = $salt;
                $fields['password'] = $passwordHash;
            }

            $userFields = ArrayToolkit::parts($fields, [
                'email',
                'nickname',
                'verifiedMobile',
                'roles',
                'salt',
                'password'
            ]);
            $user = $this->getUserDao()->update($id, $userFields);
            $this->dispatchEvent('user.update', new Event(['user' => $user, 'fields' => $fields]));
            $profileFields = ArrayToolkit::parts($fields, [
                'truename',
                'mobile',
            ]);
            $this->updateUserProfile($id, $profileFields);
            $this->commit();
            return true;
        } catch (\Exception $exception) {
            $this->rollback();
            throw $exception;
        }
    }

    public function updateUserProfile($id, $fields, $strict = true)
    {
        $user = $this->getUser($id);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        $fields = ArrayToolkit::filter($fields, [
            'truename' => '',
            'gender' => 'secret',
            'iam' => '',
            'idcard' => '',
            'birthday' => null,
            'city' => '',
            'mobile' => '',
            'qq' => '',
            'school' => '',
            'class' => '',
            'company' => '',
            'job' => '',
            'signature' => '',
            'title' => '',
            'about' => '',
            'weibo' => '',
            'weixin' => '',
            'site' => '',
            'isWeiboPublic' => '',
            'isWeixinPublic' => '',
            'isQQPublic' => '',
            'intField1' => null,
            'intField2' => null,
            'intField3' => null,
            'intField4' => null,
            'intField5' => null,
            'dateField1' => null,
            'dateField2' => null,
            'dateField3' => null,
            'dateField4' => null,
            'dateField5' => null,
            'floatField1' => null,
            'floatField2' => null,
            'floatField3' => null,
            'floatField4' => null,
            'floatField5' => null,
            'textField1' => '',
            'textField2' => '',
            'textField3' => '',
            'textField4' => '',
            'textField5' => '',
            'textField6' => '',
            'textField7' => '',
            'textField8' => '',
            'textField9' => '',
            'textField10' => '',
            'varcharField1' => '',
            'varcharField2' => '',
            'varcharField3' => '',
            'varcharField4' => '',
            'varcharField5' => '',
            'varcharField6' => '',
            'varcharField7' => '',
            'varcharField8' => '',
            'varcharField9' => '',
            'varcharField10' => '',
        ]);

        if (empty($fields)) {
            return $this->getProfileDao()->get($id);
        }

        unset($fields['title']);

        if (!empty($fields['gender']) && !in_array($fields['gender'], ['male', 'female', 'secret'])) {
            $this->createNewException(UserException::GENDER_INVALID());
        }

        if (!empty($fields['birthday']) && !SimpleValidator::date($fields['birthday'])) {
            $this->createNewException(UserException::BIRTHDAY_INVALID());
        }

        if (!empty($fields['mobile']) && !SimpleValidator::mobile($fields['mobile'])) {
            $this->createNewException(UserException::MOBILE_INVALID());
        }

        if (!empty($fields['qq']) && !SimpleValidator::qq($fields['qq'])) {
            $this->createNewException(UserException::QQ_INVALID());
        }

        if (!empty($fields['weixin']) && !SimpleValidator::weixin($fields['weixin'])) {
            $this->createNewException(UserException::WEIXIN_INVALID());
        }

        if (!empty($fields['about'])) {
            $currentUser = $this->biz['user'];
            $trusted = $currentUser->isAdmin();
            $fields['about'] = $this->purifyHtml($fields['about'], $trusted);
        }

        if (!empty($fields['site']) && !SimpleValidator::site($fields['site'])) {
            $this->createNewException(UserException::SITE_INVALID());
        }
        if (!empty($fields['weibo']) && !SimpleValidator::site($fields['weibo'])) {
            $this->createNewException(UserException::WEIBO_INVALID());
        }
        if (!empty($fields['blog']) && !SimpleValidator::site($fields['blog'])) {
            $this->createNewException(UserException::BLOG_INVALID());
        }

        $fields = $this->filterCustomField($fields);
        if (empty($fields['isWeiboPublic'])) {
            $fields['isWeiboPublic'] = 0;
        } else {
            $fields['isWeiboPublic'] = 1;
        }

        if (empty($fields['isWeixinPublic'])) {
            $fields['isWeixinPublic'] = 0;
        } else {
            $fields['isWeixinPublic'] = 1;
        }

        if (empty($fields['isQQPublic'])) {
            $fields['isQQPublic'] = 0;
        } else {
            $fields['isQQPublic'] = 1;
        }

        if ($strict) {
            $fields = array_filter($fields, function ($value) {
                if (0 === $value) {
                    return true;
                }

                return !empty($value);
            });
        }

        $userProfile = $this->getProfileDao()->update($id, $fields);
        $this->dispatchEvent('profile.update', new Event(['user' => $user, 'fields' => $fields]));

        return $userProfile;
    }

    public function changeUserRoles($id, array $roles, $currentUser)
    {
        if (empty($roles)) {
            $this->createNewException(UserException::ROLES_INVALID());
        }

        $user = $this->getUser($id);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        if (!in_array('ROLE_PARTER_ADMIN', $roles)) {
            $this->createNewException(UserException::ROLES_INVALID());
        }

        $currentUserRoles = $currentUser['roles'];

        $hiddenRoles = [];
        if (!in_array('ROLE_SUPER_ADMIN', $currentUser['roles'])) {
            $userRoles = $user['roles'];
            $hiddenRoles = array_diff($userRoles, $currentUserRoles);
        }

        $allowedRoles = array_merge($currentUserRoles, ArrayToolkit::column($this->getRoleService()->searchRoles(['createdUserId' => $currentUser['id']], 'created', 0, 9999), 'code'));
        $notAllowedRoles = array_diff($roles, $allowedRoles);

        if (!empty($notAllowedRoles) && !in_array('ROLE_SUPER_ADMIN', $currentUser['roles'], true)) {
            $this->createNewException(UserException::ROLES_INVALID());
        }

        $roles = array_merge($roles, $hiddenRoles);

        $user = $this->getUserDao()->update($id, ['roles' => $roles]);

        return UserSerialize::unserialize($user);
    }

    public function makeToken($type, $userId = null, $expiredTime = null, $data = '', $args = [])
    {
        $token = [];
        $token['type'] = $type;
        $token['userId'] = $userId ? (int)$userId : 0;
        $token['token'] = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        $token['data'] = $data;
        $token['times'] = empty($args['times']) ? 0 : (int)($args['times']);
        $token['expiredTime'] = $expiredTime ? (int)$expiredTime : 0;
        $token['createdTime'] = time();
        $token = $this->getUserTokenDao()->create($token);

        return $token['token'];
    }

    public function getToken($type, $token)
    {
        $token = $this->getUserTokenDao()->getByToken($token);

        if (empty($token) || $token['type'] != $type) {
            return null;
        }

        if ($token['expiredTime'] > 0 && $token['expiredTime'] < time()) {
            return null;
        }

        return $token;
    }

    public function countTokens($conditions)
    {
        return $this->getUserTokenDao()->count($conditions);
    }

    public function deleteToken($type, $token)
    {
        $token = $this->getUserTokenDao()->getByToken($token);

        if (empty($token) || $token['type'] != $type) {
            return false;
        }

        $this->getUserTokenDao()->delete($token['id']);

        return true;
    }

    public function findBindsByUserId($userId)
    {
        $user = $this->getUserDao()->get($userId);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        return $this->getUserBindDao()->findByToId($userId);
    }

    public function unBindUserByTypeAndToId($type, $toId)
    {
        $user = $this->getUserDao()->get($toId);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        if (!$this->typeInOAuthClient($type)) {
            $this->createNewException(UserException::CLIENT_TYPE_INVALID());
        }

        $bind = $this->getUserBindByTypeAndUserId($type, $toId);
        if ($bind) {
            $convertedType = $this->convertOAuthType($type);
            $this->getUserBindDao()->deleteByTypeAndToId($convertedType, $toId);
            $currentUser = $this->getCurrentUser();
            $this->dispatchEvent('user.unbind', new Event($user, ['bind' => $bind, 'bindType' => $type, 'convertedType' => $convertedType]));
            $this->getLogService()->info('user', 'unbind', sprintf('用户名%s解绑成功，操作用户为%s', $user['nickname'], $currentUser['nickname']));
        }

        return $bind;
    }

    public function getUserBindByTypeAndFromId($type, $fromId)
    {
        $type = $this->convertOAuthType($type);

        return $this->getUserBindDao()->getByTypeAndFromId($type, $fromId);
    }

    public function findUserBindByTypeAndFromIds($type, $fromIds)
    {
        $type = $this->convertOAuthType($type);

        return $this->getUserBindDao()->findByTypeAndFromIds($type, $fromIds);
    }

    public function findUserBindByTypeAndToIds($type, $toIds)
    {
        $type = $this->convertOAuthType($type);

        return $this->getUserBindDao()->findByTypeAndToIds($type, $toIds);
    }

    public function getUserBindByToken($token)
    {
        return $this->getUserBindDao()->getByToken($token);
    }

    public function getUserBindByTypeAndUserId($type, $toId)
    {
        $user = $this->getUserDao()->get($toId);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        if (!$this->typeInOAuthClient($type)) {
            $this->createNewException(UserException::CLIENT_TYPE_INVALID());
        }

        $type = $this->convertOAuthType($type);

        return $this->getUserBindDao()->getByToIdAndType($type, $toId);
    }

    public function findUserBindByTypeAndUserId($type, $toId)
    {
        $user = $this->getUserDao()->get($toId);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        $type = $this->convertOAuthType($type);

        return $this->getUserBindDao()->findByToIdAndType($type, $toId);
    }

    public function bindUser($type, $fromId, $toId, $token)
    {
        $user = $this->getUserDao()->get($toId);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        if (!$this->typeInOAuthClient($type)) {
            $this->createNewException(UserException::CLIENT_TYPE_INVALID());
        }

        $convertedType = $this->convertOAuthType($type);

        $bind = $this->getUserBindDao()->create([
            'type' => $convertedType,
            'fromId' => $fromId,
            'toId' => $toId,
            'token' => empty($token['token']) ? '' : $token['token'],
            'createdTime' => time(),
            'expiredTime' => empty($token['expiredTime']) ? 0 : $token['expiredTime'],
        ]);

        $this->dispatchEvent('user.bind', new Event($user, ['bind' => $bind, 'bindType' => $type, 'convertedType' => $convertedType, 'token' => $token]));
    }

    public function markLoginInfo($user, $type = null)
    {
        $this->getUserDao()->update($user['id'], [
            'loginIp' => $user['currentIp'],
            'loginTime' => time(),
        ]);
        //if user type is system,we do not record user login log
        if ('system' == $user['type']) {
            return false;
        }

        $this->refreshLoginSecurityFields($user['id'], $user['currentIp']);

        $this->getLogService()->info('user', 'login_success', Constants::getLoginTypeItems($type) . '成功', [
            'currentIp' => $user['currentIp']
        ]);
    }

    public function markLoginFailed($userId, $ip)
    {
//        $user = $userId ? $this->getUser($userId) : null;
//
//        $setting = $this->getSettingService()->get('login_bind', []);
//
//        $default = [
//            'temporary_lock_enabled' => 0,
//            'temporary_lock_allowed_times' => 5,
//            'temporary_lock_minutes' => 20,
//        ];
//        $setting = array_merge($default, $setting);
//
//        $fields = [];
//
//        if ($user && $setting['temporary_lock_enabled']) {
//            if (time() > $user['lastPasswordFailTime'] + $setting['temporary_lock_minutes'] * 60) {
//                $fields['consecutivePasswordErrorTimes'] = 1;
//            } else {
//                $fields['consecutivePasswordErrorTimes'] = $user['consecutivePasswordErrorTimes'] + 1;
//            }
//
//            if ($fields['consecutivePasswordErrorTimes'] >= $setting['temporary_lock_allowed_times']) {
//                $fields['lockDeadline'] = time() + $setting['temporary_lock_minutes'] * 60;
//            }
//
//            $fields['lastPasswordFailTime'] = time();
//
//            $user = $this->getUserDao()->update($user['id'], $fields);
//        }
//
//        if ($user) {
//            $log = sprintf('用户(%s)，', $user['nickname']) . ($user['consecutivePasswordErrorTimes'] ? sprintf('连续第%u次登录失败', $user['consecutivePasswordErrorTimes']) : '登录失败');
//        } else {
//            $log = sprintf('用户(IP: %s)，', $ip) . ($user['consecutivePasswordErrorTimes'] ? sprintf('连续第%u次登录失败', $user['consecutivePasswordErrorTimes']) : '登录失败');
//        }
//
//        $this->getLogService()->info('user', 'login_fail', $log);
//
//        $ipFailedCount = $this->getIpBlacklistService()->increaseIpFailedCount($ip);
//
//        return [
//            'failedCount' => $user['consecutivePasswordErrorTimes'],
//            'leftFailedCount' => $setting['temporary_lock_allowed_times'] - $user['consecutivePasswordErrorTimes'],
//            'ipFaildCount' => $ipFailedCount,
//        ];
    }

    public function refreshLoginSecurityFields($userId, $ip)
    {
        $fields = [
            'lockDeadline' => 0,
            'consecutivePasswordErrorTimes' => 0,
            'lastPasswordFailTime' => 0,
        ];

        $this->getUserDao()->update($userId, $fields);
        $this->getIpBlacklistService()->clearFailedIp($ip);
    }

    public function checkLoginForbidden($userId, $ip)
    {
//        $user = $userId ? $this->getUser($userId) : null;
//
//        $setting = $this->getSettingService()->get('login_bind', []);
//
//        $default = [
//            'temporary_lock_enabled' => 0,
//            'temporary_lock_allowed_times' => 5,
//            'ip_temporary_lock_allowed_times' => 20,
//            'temporary_lock_minutes' => 20,
//        ];
//        $setting = array_merge($default, $setting);
//
//        if (empty($setting['temporary_lock_enabled'])) {
//            return ['status' => 'ok'];
//        }
//
//        $ipFailedCount = $this->getIpBlacklistService()->getIpFailedCount($ip);
//
//        if ($ipFailedCount >= $setting['ip_temporary_lock_allowed_times']) {
//            return ['status' => 'error', 'code' => 'max_ip_failed_limit'];
//        }
//
//        if ($user && $setting['temporary_lock_enabled'] && ($user['lockDeadline'] > time())) {
//            return ['status' => 'error', 'code' => 'max_failed_limit'];
//        }
//
//        if ($user && $setting['temporary_lock_enabled'] && ($user['consecutivePasswordErrorTimes'] >= $setting['temporary_lock_allowed_times']) && ($user['lockDeadline'] > time())) {
//            return ['status' => 'error', 'code' => 'max_failed_limit'];
//        }

        return ['status' => 'ok'];
    }

    public function lockUser($id)
    {
        $user = $this->getUser($id);
        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        if (in_array('ROLE_SUPER_ADMIN', $user['roles'])) {
            $this->createNewException(UserException::LOCK_DENIED());
        }
        $this->getUserDao()->update($user['id'], ['locked' => 1]);
        $this->dispatchEvent('user.lock', new Event($user));

        return true;
    }

    public function unlockUser($id)
    {
        $user = $this->getUser($id);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        $this->getUserDao()->update($user['id'], ['locked' => 0]);

        $this->dispatchEvent('user.unlock', new Event($user));

        return true;
    }

    public function promoteUser($id, $number)
    {
        $user = $this->getUser($id);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        $user = $this->getUserDao()->update($user['id'], ['promoted' => 1, 'promotedSeq' => $number, 'promotedTime' => time()]);
        $this->getLogService()->info('user', 'recommend', "推荐用户{$user['nickname']}(#{$user['id']})");

        return $user;
    }

    public function cancelPromoteUser($id)
    {
        $user = $this->getUser($id);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        $user = $this->getUserDao()->update($user['id'], ['promoted' => 0, 'promotedSeq' => 0, 'promotedTime' => 0]);

        $this->getLogService()->info('user', 'cancel_recommend', sprintf('取消推荐用户%s(#%u)', $user['nickname'], $user['id']));

        return $user;
    }

    public function findLatestPromotedTeacher($start, $limit)
    {
        return $this->searchUsers(['roles' => '|ROLE_TEACHER|', 'promoted' => 1], ['promotedTime' => 'DESC'], $start, $limit);
    }

    public function waveUserCounter($userId, $name, $number)
    {
        if (!ctype_digit((string)$number)) {
            $this->createNewException(CommonException::ERROR_PARAMETER());
        }

        $this->getUserDao()->waveCounterById($userId, $name, $number);
    }

    public function clearUserCounter($userId, $name)
    {
        $this->getUserDao()->deleteCounterById($userId, $name);
    }

    public function hasAdminRoles($userId)
    {
        $user = $this->getUser($userId);

        $roles = $this->getRoleService()->findRolesByCodes($user['roles']);

        foreach ($roles as $role) {
            if (in_array('admin', $role['data'], true)) {
                return true;
            }

            if (in_array('admin_v2', $role['data_v2'], true)) {
                return true;
            }
        }

        return false;
    }

    public function dropFieldData($fieldName)
    {
        $this->getProfileDao()->dropFieldData($fieldName);
    }

    public function rememberLoginSessionId($id, $sessionId)
    {
        $user = $this->getUser($id);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        return $this->getUserDao()->update($id, [
            'loginSessionId' => $sessionId,
        ]);
    }

    public function analysisRegisterDataByTime($startTime, $endTime)
    {
        return $this->getUserDao()->analysisRegisterDataByTime($startTime, $endTime);
    }

    public function parseAts($text)
    {
        preg_match_all('/@([\x{4e00}-\x{9fa5}\w]{2,16})/u', $text, $matches);

        $users = $this->getUserDao()->findByNicknames(array_unique($matches[1]));

        if (empty($users)) {
            return [];
        }

        $ats = [];

        foreach ($users as $user) {
            $ats[$user['nickname']] = $user['id'];
        }

        return $ats;
    }

    public function getUserByInviteCode($inviteCode)
    {
        return $this->getUserDao()->getByInviteCode($inviteCode);
    }

    public function findUserIdsByInviteCode($inviteCode)
    {
        $inviteUser = $this->getUserDao()->getByInviteCode($inviteCode);
        $record = $this->getInviteRecordService()->findRecordsByInviteUserId($inviteUser['id']);
        $userIds = ArrayToolkit::column($record, 'invitedUserId');

        return $userIds;
    }

    public function createInviteCode($userId)
    {
        $inviteCode = StringToolkit::createRandomString(5);
        $inviteCode = strtoupper($inviteCode);
        $code = [
            'inviteCode' => $inviteCode,
        ];

        return $this->getUserDao()->update($userId, $code);
    }

    public function findUnlockedUserMobilesByUserIds($userIds)
    {
        if (empty($userIds)) {
            return [];
        }

        $conditions = [
            'locked' => 0,
            'userIds' => $userIds,
        ];

        $conditions['hasVerifiedMobile'] = true;
        $count = $this->countUsers($conditions);
        $users = $this->searchUsers($conditions, ['createdTime' => 'ASC'], 0, $count);
        $mobiles = ArrayToolkit::column($users, 'verifiedMobile');

        return $mobiles;
    }

    public function updateUserLocale($id, $locale)
    {
        $this->getUserDao()->update($id, ['locale' => $locale]);
    }

    public function getUserIdsByKeyword($keyword)
    {
        if (empty($keyword)) {
            return [-1];
        }

        if (SimpleValidator::email($keyword)) {
            $user = $this->getUserByEmail($keyword);

            return $user ? [$user['id']] : [-1];
        }

        if (SimpleValidator::mobile($keyword)) {
            $mobileVerifiedUser = $this->getUserByVerifiedMobile($keyword);
            $profileUsers = $this->searchUserProfiles(
                ['tel' => $keyword],
                ['id' => 'DESC'],
                0,
                PHP_INT_MAX
            );
            $mobileNameUser = $this->getUserByNickname($keyword);

            $userIds = $profileUsers ? ArrayToolkit::column($profileUsers, 'id') : [];
            $userIds[] = $mobileVerifiedUser ? $mobileVerifiedUser['id'] : null;
            $userIds[] = $mobileNameUser ? $mobileNameUser['id'] : null;
            $userIds = array_unique($userIds);

            return $userIds ?: [-1];
        }

        $users = $this->searchUsers(
            ['nickname' => $keyword],
            ['id' => 'DESC'],
            0,
            $this->countUsers(['nickname' => $keyword]),
            ['id']
        );
        $userIds = ArrayToolkit::column($users, 'id');

        return $userIds ?: [-1];
    }

    public function updateUserNewMessageNum($id, $num)
    {
        $user = $this->getCurrentUser();
        if (!$user->isLogin()) {
            return;
        }
        $newMessageNum = $user['newMessageNum'] - 1;
        if ($newMessageNum >= 0 && $num > 0) {
            $this->getUserDao()->update($id, ['newMessageNum' => $newMessageNum]);
            $user->__set('newMessageNum', $newMessageNum);
        }
    }

    public function makeUUID()
    {
        return sha1(uniqid(mt_rand(), true));
    }

    public function generateUUID()
    {
        $uuid = $this->makeUUID();
        $user = $this->getUserByUUID($uuid);

        if (empty($user)) {
            return $uuid;
        } else {
            return $this->generateUUID();
        }
    }


    public function initPassword($id, $newPassword)
    {
        $this->beginTransaction();

        try {
            $fields = [
                'passwordInit' => 1,
            ];

            $this->getAuthService()->changePassword($id, null, $newPassword);
            $this->getUserDao()->update($id, $fields);

            $this->commit();
        } catch (\Exception $e) {
            $this->rollback();
            throw $e;
        }

        return $this->getUserDao()->update($id, $fields);
    }

    public function validatePassword($password)
    {
        $auth = $this->getSettingService()->get('auth', []);
        $passwordLevel = empty($auth['password_level']) ? 'low' : $auth['password_level'];
        if ('low' == $passwordLevel && SimpleValidator::lowPassword($password)) {
            return true;
        }
        if ('middle' == $passwordLevel && SimpleValidator::middlePassword($password)) {
            return true;
        }
        if ('high' == $passwordLevel && SimpleValidator::highPassword($password)) {
            return true;
        }

        return false;
    }

    public function setFaceRegistered($id)
    {
        return $this->getUserDao()->update($id, ['faceRegistered' => 1]);
    }

    public function findUnLockedUsersByUserIds($userIds = [])
    {
        return $this->getUserDao()->findUnLockedUsersByUserIds($userIds);
    }

    public function updatePasswordChanged($id, $passwordChanged)
    {
        return $this->getUserDao()->update($id, ['passwordChanged' => $passwordChanged]);
    }

    protected function getRandomChar()
    {
        return base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
    }

    protected function validateNickname($nickname)
    {
        if (!SimpleValidator::nickname($nickname)) {
            $this->createNewException(UserException::NICKNAME_INVALID());
        }
    }

    protected function decideUserJustStudentRole($userId)
    {
        $user = $this->getUserDao()->get($userId);

        if (empty($user)) {
            $this->createNewException(UserException::NOTFOUND_USER());
        }

        if (count(array_intersect($user['roles'], ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN', 'ROLE_TEACHER', 'ROLE_TEACHER_ASSISTANT', 'ROLE_EDUCATIONAL_ADMIN'])) > 0) {
            return false;
        }

        return true;
    }

    protected function filterCustomField($fields)
    {
        $numericalFields = ['intField1', 'intField2', 'intField3', 'intField4', 'intField5', 'floatField1', 'floatField2', 'floatField3', 'floatField4', 'floatField5'];
        foreach ($numericalFields as $field) {
            if (isset($fields[$field]) && empty($fields[$field])) {
                $fields[$field] = null;
            }
        }

        $dateFields = ['dateField1', 'dateField2', 'dateField3', 'dateField4', 'dateField5'];
        foreach ($dateFields as $dateField) {
            if (isset($fields[$dateField]) && empty($fields[$dateField])) {
                $fields[$dateField] = null;
            }

            if (!empty($fields[$dateField]) && !SimpleValidator::date($fields[$dateField])) {
                $this->createNewException(UserException::DATEFIELD_INVALID());
            }
        }

        return $fields;
    }

    protected function _prepareApprovalConditions($conditions)
    {
        if (!empty($conditions['keywordType']) && 'truename' == $conditions['keywordType']) {
            $conditions['truename'] = trim($conditions['keyword']);
        }

        if (!empty($conditions['keywordType']) && 'idcard' == $conditions['keywordType']) {
            $conditions['idcard'] = trim($conditions['keyword']);
        }

        unset($conditions['keywordType']);
        unset($conditions['keyword']);

        return $conditions;
    }

    // #72812 修复越权删除头像漏洞
    protected function canManageAvatarFile($userId, $file)
    {
        if ($userId != $file['userId']) {
            return false;
        }

        return true;
    }

    protected function makePasswordHashAndSalt($password)
    {
        $salt = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        $passwordHash = $this->getPasswordEncoder()->encodePassword($password, $salt);

        return [$salt, $passwordHash];
    }

    /**
     * @return AuthService
     */
    protected function getAuthService()
    {
        return $this->createService('User:AuthService');
    }

    /**
     * @return UserDao
     */
    protected function getUserDao()
    {
        return $this->createDao('User:UserDao');
    }

    /**
     * @return UserProfileDao
     */
    protected function getProfileDao()
    {
        return $this->createDao('User:UserProfileDao');
    }

    /**
     * @return UserBindDao
     */
    protected function getUserBindDao()
    {
        return $this->createDao('User:UserBindDao');
    }

    /**
     * @return TokenDao
     */
    protected function getUserTokenDao()
    {
        return $this->createDao('User:TokenDao');
    }


    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('Setting:SettingService');
    }

    /**
     * @return SystemLogService
     */
    protected function getLogService()
    {
        return $this->createService('SystemLog:SystemLogService');
    }

    /**
     * @return IpBlacklistService
     */
    protected function getIpBlacklistService()
    {
        return $this->createService('IpBlacklist:IpBlacklistService');
    }

    protected function getPasswordEncoder()
    {
        return new MessageDigestPasswordEncoder('sha256');
    }

    /**
     * @return BlacklistService
     */
    protected function getBlacklistService()
    {
        return $this->createService('User:BlacklistService');
    }

    /**
     * @return InviteRecordService
     */
    protected function getInviteRecordService()
    {
        return $this->createService('User:InviteRecordService');
    }

    /**
     * @return RoleService
     */
    protected function getRoleService()
    {
        return $this->createService('Role:RoleService');
    }

    public function getKernel()
    {
        return ServiceKernel::instance();
    }

    /**
     * @param $type
     *
     * @return string
     */
    private function convertOAuthType($type)
    {
        if ('weixinweb' == $type || 'weixinmob' == $type) {
            $type = 'weixin';
        }

        return $type;
    }

    private function createImgCropOptions($naturalSize, $scaledSize)
    {
        $options = [];

        $options['x'] = 0;
        $options['y'] = 0;
        $options['x2'] = $scaledSize->getWidth();
        $options['y2'] = $scaledSize->getHeight();
        $options['w'] = $naturalSize->getWidth();
        $options['h'] = $naturalSize->getHeight();

        $options['imgs'] = [];
        $options['imgs']['large'] = [200, 200];
        $options['imgs']['medium'] = [120, 120];
        $options['imgs']['small'] = [48, 48];
        $options['width'] = $naturalSize->getWidth();
        $options['height'] = $naturalSize->getHeight();

        return $options;
    }

}
