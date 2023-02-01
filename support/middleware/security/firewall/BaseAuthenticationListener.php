<?php

namespace support\middleware\security\firewall;


use support\middleware\security\authentication\token\ApiToken;
use Biz\User\CurrentUser;
use Biz\User\Service\UserService;
use Biz\User\Exception\UserException;
use Codeages\Biz\Framework\Context\Biz;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Webman\Http\Request;

abstract class BaseAuthenticationListener implements ListenerInterface
{
    protected $biz;

    public function __construct(Biz $biz)
    {
        $this->biz = $biz;
    }

    protected function checkUserLocked($userId)
    {
        $user = $this->getUserService()->getUser($userId);

        if (empty($user)) {
            return;
        }

        if ($user['locked']) {
            throw UserException::LOCKED_USER();
        }
    }

    protected function createTokenFromRequest(Request $request, $userId, $loginToken = '')
    {
        $user = $this->getUserService()->getUser($userId);
        $currentUser = new CurrentUser();
        $user['currentIp'] = $request->getRealIp();
        $user['loginToken'] = $loginToken;
        $currentUser->fromArray($user);
//        $currentUser->setPermissions(PermissionBuilder::instance()->getPermissionsByRoles($currentUser->getRoles()));

        return new ApiToken($currentUser, $currentUser->getRoles());
    }

    /**
     * @return TokenStorageInterface
     */
    protected function getTokenStorage()
    {
        return $this->biz['api.security.token_storage'];
    }

    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->biz->service('User:UserService');
    }
}