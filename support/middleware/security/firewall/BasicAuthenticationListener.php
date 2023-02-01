<?php


namespace support\middleware\security\firewall;


use Biz\User\Exception\UserException;
use Webman\Http\Request;

class BasicAuthenticationListener extends BaseAuthenticationListener
{

    public function handle(Request $request)
    {
        $authorization = $request->header('authorization', '');
        list($admin, $password) = $this->parseAuthData(str_replace('Basic ', '', $authorization));
        $user = $this->validateUser($request, $admin, $password);
        // 登记CurrentUser
        $token = $this->createTokenFromRequest($request, $user['id']);
        $this->getTokenStorage()->setToken($token);
    }

    protected function parseAuthData(string $authorization): array
    {
        return explode(':', base64_decode($authorization));
    }

    protected function validateUser(Request $request, $admin, $password)
    {
        if (empty($admin) || empty($password)) {
            throw UserException::USERNAME_PASSWORD_ERROR();
        }

        $checkCaptcha = $request->post('checkCaptcha', false);

        if ($checkCaptcha) {
            $captcha = $request->post('captcha');
            if (empty($captcha)) {
                throw UserException::CAPTCHA_EMPTY();
            }

            if (strtolower($captcha) !== $request->session()->get('captcha')) {
                throw UserException::CAPTCHA_ERROR();
            }
        }

        $user = $this->getUserService()->getUserByLoginField($admin);
        if (empty($user)) {
            throw UserException::NOTFOUND_USER();
        }

        if (!$this->getUserService()->verifyPassword($user['id'], $password)) {
            throw UserException::PASSWORD_ERROR();
        }

        if ($user['locked']) {
            throw UserException::LOCKED_USER();
        }

        return $user;
    }
}