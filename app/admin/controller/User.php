<?php


namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\filters\UserFilter;
use Biz\User\Service\AuthService;
use Biz\User\Service\TokenService;
use Biz\Role\Service\RoleService;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;
use support\Request;
use support\utils\Paginator;
use support\utils\ArrayToolkit;

class User extends BaseController
{
    /**
     * @param Request $request
     */
    public function users(Request $request)
    {
        $params = $request->get();
        $conditions = ['noType' => 'system'];
        if (isset($params['locked']) && $params['locked'] !== '') {
            $conditions['locked'] = (int)$params['locked'];
        }

        if (!empty($params['keywords'])) {
            $conditions['keywords'] = $params['keywords'];
        }

        $total = $this->getUserService()->countUsers($conditions);
        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $sort = $this->getSort($request);
        $sort['locked'] = 'ASC';
        $paginator = new Paginator($offset, $total, $request->uri(), $limit);
        $users = $this->getUserService()->searchUsers($conditions, $sort, $paginator->getOffsetCount(), $paginator->getPerPageCount());
        $roles = $this->getRoleService()->searchRoles([], [], 0, PHP_INT_MAX, ['code', 'name']);
        $codes = ArrayToolKit::index($roles, 'code');
        $userFilter = new UserFilter();
        $userFilter->filters($users);
        foreach ($users as &$user) {
            $roleTitles = [];
            foreach ($user['roles'] as $role) {
                $roleTitles[] = $codes[$role]['name'] ?? '';
            }
            $user['roleTitles'] = array_filter($roleTitles);
        }

        return $this->createSuccessJsonResponse([
            'users' => $users,
            'paginator' => Paginator::toArray($paginator)
        ]);
    }

    /**
     * @param Request $request
     * @return \support\Response
     */
    public function detail(Request $request)
    {
        $id = $request->get('id', $this->getCurrentUser()->getId());
        $user = $this->getUserService()->getUser($id);
        if (empty($user)) {
            return $this->createFailJsonResponse('获取用户信息失败');
        }

        $userProfile = $this->getUserService()->getUserProfile($id);
        $user['truename'] = $userProfile['truename'];

        return $this->createSuccessJsonResponse($this->filterUserInfo($user));
    }

    public function logout(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            return response('<center><h1>404 Not Found</h1></center>', 404);
        }
        $user = $this->getCurrentUser()->toArray();
        $this->getLogService()->info('admin', 'user_logout', '用户退出', [
            'userToken' => $user,
            'currentIp' => $request->getRealIp()
        ]);
        $this->getTokenService()->destoryToken($user['loginToken']);
        $this->getBiz()->offsetSet('user', null);

        return $this->createSuccessJsonResponse(null, '登出成功');
    }

    public function roles(Request $request)
    {
        $roles = $this->getRoleService()->searchRoles(
            ['createdUserId' => $this->getCurrentUser()->getId()],
            ['createdTime' => 'DESC'], 0, PHP_INT_MAX, ['code', 'name']);

        return $this->createSuccessJsonResponse($roles);
    }

    /**
     *
     * 校验用户名
     * @param Request $request
     * @return \support\Response
     */
    public function checkNickname(Request $request)
    {
        $nickname = $request->get('nickname');
        list($result, $message) = $this->getAuthService()->checkUsername($nickname);
        if ('success' !== $result) {
            return $this->createFailJsonResponse($message);
        }

        return $this->createSuccessJsonResponse($message);
    }

    /**
     *
     * 校验邮箱
     * @param Request $request
     * @return \support\Response
     */
    public function checkEmail(Request $request)
    {
        $email = $request->get('email');
        list($result, $message) = $this->getAuthService()->checkEmail($email);
        if ('success' !== $result) {
            return $this->createFailJsonResponse($message);
        }

        return $this->createSuccessJsonResponse($message);
    }

    /**
     *
     * 校验手机号
     * @param Request $request
     * @return \support\Response
     */
    public function checkMobile(Request $request)
    {
        $mobile = $request->get('mobile');
        $userId = $request->get('userId');
        if (!empty($userId)) {
            $user = $this->getUserService()->getUser($userId);
            if (!empty($user) && $user['verifiedMobile'] === $mobile) {
                return $this->createSuccessJsonResponse();
            }
        }

        list($result, $message) = $this->getAuthService()->checkMobile($mobile);
        if ('success' !== $result) {
            return $this->createFailJsonResponse($message);
        }

        return $this->createSuccessJsonResponse($message);
    }

    public function create(Request $request)
    {
        $formData = $request->post();
        $formData = $this->checkRequiredFields([
            'truename',
            'email',
            'nickname',
            'password',
            'confirmPassword',
            'verifiedMobile',
            'roles',
        ], $formData);

        $formData['type'] = 'default';
        $registration = $this->getRegisterData($formData);
        $user = $this->getAuthService()->register($registration);
        $this->getLogService()->info('user', 'add', "管理员添加新用户 {$user['nickname']} ({$user['id']})", [
            'currentIp' => $request->getRealIp()
        ]);

        return $this->createSuccessJsonResponse(null, '创建成功');
    }

    public function edit(Request $request)
    {
        $id = $request->get('id');
        if (empty($id)) {
            return $this->createFailJsonResponse('参数缺失');
        }

        $formData = $request->post();
        $formData = $this->checkRequiredFields([
            'truename',
            'email',
            'nickname',
            'verifiedMobile',
            'roles',
        ], $formData);
        $formData['mobile'] = $formData['verifiedMobile'];
        $result = $this->getUserService()->updateUserAndUserProfileWithAdmin($id, $formData);
        if ($result) {
            return $this->createSuccessJsonResponse(null, '更新成功');
        }

        return $this->createFailJsonResponse('更新失败');

    }

    public function lock(Request $request)
    {
        $userId = $request->post('userId');
        if (empty($userId)) {
            throw  new NotFoundException("访问不存在");
        }

        $this->getUserService()->lockUser($userId);

        return $this->createSuccessJsonResponse(null, '封禁成功');
    }

    protected function filterUserInfo($user)
    {
        $userFilter = new UserFilter();

        $userFilter->filter($user);

        return $user;
    }

    protected function getRegisterData($formData)
    {
        $createFields = [
            'nickname',
            'password',
            'type',
            'email',
            'roles',
            'verifiedMobile',
            'truename',
        ];
        $userData = ArrayToolkit::parts($formData, $createFields);
        $userData['orgCodes'] = "";
        !empty($userData['verifiedMobile']) && $userData['mobile'] = $userData['verifiedMobile'];

        return $userData;
    }

    /**
     * @return AuthService
     */
    protected function getAuthService()
    {
        return $this->createService('User:AuthService');
    }

    /**
     * @return TokenService
     */
    protected function getTokenService()
    {
        return $this->createService('User:TokenService');
    }

    /**
     *
     * @return RoleService
     */
    protected function getRoleService()
    {
        return $this->createService('Role:RoleService');
    }
}