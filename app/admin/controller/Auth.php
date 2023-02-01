<?php


namespace app\admin\controller;


use app\admin\BaseController;
use app\admin\filters\TokenFilter;
use Biz\Constants;
use Biz\User\Service\TokenService;
use Gregwar\Captcha\CaptchaBuilder;
use support\Request;

class Auth extends BaseController
{
    const LOGIN_DURATION = 3600 * 24 * 7;

    public function beforeAction(Request $request)
    {
    }

    public function login(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            return response('<center><h1>404 Not Found</h1></center>', 404);
        }
        $type = $request->get('type', Constants::TOKEN_TYPE_ADMIN_LOGIN);
        $user = $this->getCurrentUser()->toArray();
        $token = $this->makeToken($type, $this->getCurrentUser()->getId(), self::LOGIN_DURATION);
        $this->appendUser($user);
        $user['currentIp'] = $request->getRealIp();
        $this->getUserService()->markLoginInfo($user, $type);
        $delTokens = $this->getTokenService()->findTokensByUserIdAndType($user['id'], $type);
        foreach ($delTokens as $delToken) {
            if ($delToken['token'] != $token['token']) {
                $this->getTokenService()->destoryToken($delToken['token']);
            }
        }

        $data = [
            'token' => [
                'value' => $token['token'],
                'type' => $token['type']
            ],
            'user' => $user,
        ];
        // TODO：后期
        $tokenFilter = new TokenFilter();
        $tokenFilter->filter($data);

        return $this->createSuccessJsonResponse($data, '登录成功');
    }

    public function captcha(Request $request)
    {
        // 初始化验证码类
        $builder = new CaptchaBuilder();
        // 生成验证码
        $builder->build(100, 32);
        // 将验证码的值存储到session中
        $request->session()->set('captcha', strtolower($builder->getPhrase()));
        // 获得验证码图片二进制数据
        $imgContent = $builder->get();

        return $this->createSuccessJsonResponse(['img' => sprintf("data:image/png;base64,%s", base64_encode($imgContent))]);

    }

    protected function makeToken($type, $userId, $duration = 0, $data = null, $args = [])
    {
        $token = [];
        $token['userId'] = $userId ? (int)$userId : 0;
        $token['token'] = base_convert(sha1(uniqid(mt_rand(), true)), 16, 36);
        $token['data'] = $data;
        $token['times'] = empty($args['times']) ? 0 : (int)($args['times']);
        $token['duration'] = $duration;

        return $this->getTokenService()->makeToken($type, $token);
    }

    private function appendUser(&$user)
    {
        $profile = $this->getUserService()->getUserProfile($user['id']);
        $user = array_merge($profile, $user);

        return $user;
    }

    /**
     * @return TokenService
     */
    protected function getTokenService()
    {
        return $this->createService('User:TokenService');
    }
}