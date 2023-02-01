<?php


namespace support\middleware\security\firewall;


use Biz\Constants;
use Biz\User\Exception\UserException;
use Biz\User\Service\TokenService;
use Webman\Http\Request;

class XAuthTokenAuthenticationListener extends BaseAuthenticationListener
{
    const TOKEN_KEY = 'x-auth-token';
    const TOKEN_TYPE_KEY = 'x-auth-type';
    const TOKEN_LENGTH = 32;

    public function handle(Request $request)
    {
        $token = $request->header(self::TOKEN_KEY);
        if (empty($token)) {
            throw  UserException::NOTFOUND_TOKEN();
        }

        $authType = $request->header(self::TOKEN_TYPE_KEY, 'admin_login');
        $tokenItems = Constants::getLoginTypeItems();
        if (strlen($token) !== self::TOKEN_LENGTH || !array_key_exists($authType, $tokenItems)) {
            throw UserException::TOKEN_PARAMS_FAILED();
        }

        $rawToken = $this->getUserService()->getToken($authType, $token);
        if (empty($rawToken)) {
            throw  UserException::NOTFOUND_TOKEN();
        }

        $token = $this->createTokenFromRequest($request, $rawToken['userId'], $rawToken['token']);

        $this->getTokenStorage()->setToken($token);
    }

    /**
     * @return TokenService
     */
    protected function getTokenService()
    {
        return $this->biz->service('User:TokenService');
    }
}