<?php

namespace Biz\User\Service;


interface UserService
{
    public function getUser($id, $lock = false);

    public function getUserByUUID($uuid);

    public function initSystemUsers();

    public function getUserAndProfile($id);

    public function countUsers(array $conditions);

    public function searchUsers(array $conditions, array $orderBy, $start, $limit, $columns = []);

    public function updateUserForDestroyedAccount($userId, $destroyedId);

    public function deleteUserBindByUserId($userId);

    public function searchUserProfileCount(array $conditions);


    public function searchTokenCount($conditions);

    public function findUserLikeNickname($nickname);

    public function findUserLikeTruename($truename);

    public function getSimpleUser($id);

    public function countUsersByLessThanCreatedTime($endTime);

    public function getUserProfile($id);

    public function getUserByNickname($nickname);

    public function getUnDstroyedUserByNickname($nickname);

    /**
     * 根据用户名/邮箱/手机号精确查找用户
     * @param $keyword
     * @param false $isFilterDestroyed
     * @return mixed
     */
    public function getUserByLoginField($keyword, $isFilterDestroyed = false);

    public function getUserByVerifiedMobile($mobile);

    public function countUsersByMobileNotEmpty();

    public function countUserHasMobile($needVerified = false);

    public function findUsersHasMobile($start, $limit, $needVerified = false);

    public function getUserByEmail($email);

    public function findUsersByIds(array $ids);

    public function findUnDestroyedUsersByIds($ids);

    public function findUserProfilesByIds(array $ids);

    public function searchUserProfiles(array $conditions, array $orderBy, $start, $limit, $columns = []);

    public function setEmailVerified($userId);

    public function changeNickname($userId, $nickname);

    public function changeEmail($userId, $email);

    public function updateUserUpdatedTime($id);

    public function isNicknameAvaliable($nickname);

    public function isEmailAvaliable($email);

    public function isMobileAvaliable($mobile);

    public function changePassword($id, $password);

    public function isMobileUnique($mobile);

    public function changeMobile($id, $mobile);

    public function verifyInSaltOut($in, $salt, $out);

    public function verifyPassword($id, $password);

    public function verifyPayPassword($id, $payPassword);

    public function getUserByType($type);

    public function register($registration, $registerTypes = ['email']);

    public function generateNickname($registration, $maxLoop = 100);

    public function generateEmail($registration, $maxLoop = 100);

    public function importUpdateEmail($users);

    public function updateUserAndUserProfileWithAdmin($id, $fields);

    public function updateUserProfile($id, $fields, $strict = true);

    public function changeUserRoles($id, array $roles, $currentUser);

    public function makeToken($type, $userId = null, $expiredTime = null, $data = '', $args = []);

    public function getToken($type, $token);

    public function countTokens($conditions);

    public function deleteToken($type, $token);

    public function findBindsByUserId($userId);

    public function unBindUserByTypeAndToId($type, $toId);

    public function getUserBindByTypeAndFromId($type, $fromId);

    public function findUserBindByTypeAndFromIds($type, $fromIds);

    public function findUserBindByTypeAndToIds($type, $toIds);

    public function getUserBindByToken($token);

    public function getUserBindByTypeAndUserId($type, $toId);

    public function findUserBindByTypeAndUserId($type, $toId);

    public function bindUser($type, $fromId, $toId, $token);

    public function markLoginInfo($user, $type = null);

    public function markLoginFailed($userId, $ip);

    public function refreshLoginSecurityFields($userId, $ip);

    public function checkLoginForbidden($userId, $ip);

    public function lockUser($id);

    public function unlockUser($id);

    public function promoteUser($id, $number);

    public function cancelPromoteUser($id);

    public function findLatestPromotedTeacher($start, $limit);

    public function waveUserCounter($userId, $name, $number);

    public function clearUserCounter($userId, $name);

    public function hasAdminRoles($userId);

    public function dropFieldData($fieldName);

    public function rememberLoginSessionId($id, $sessionId);

    public function analysisRegisterDataByTime($startTime, $endTime);

    public function parseAts($text);

    public function getUserByInviteCode($inviteCode);

    public function findUserIdsByInviteCode($inviteCode);

    public function createInviteCode($userId);

    public function findUnlockedUserMobilesByUserIds($userIds);

    public function updateUserLocale($id, $locale);

    public function getUserIdsByKeyword($keyword);

    public function updateUserNewMessageNum($id, $num);

    public function makeUUID();

    public function generateUUID();

    public function initPassword($id, $newPassword);

    public function validatePassword($password);

    public function setFaceRegistered($id);

    public function findUnLockedUsersByUserIds($userIds = []);

    public function updatePasswordChanged($id, $passwordChanged);
}
