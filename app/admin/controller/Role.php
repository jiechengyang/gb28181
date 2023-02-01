<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\filters\UserFilter;
use Biz\User\Service\TokenService;
use Biz\Role\Service\RoleService;
use support\Request;
use support\utils\Paginator;

class Role extends BaseController
{

    public function roles(Request $request)
    {
        
    }

    public function items(Request $request)
    {
        $roles = $this->getRoleService()->searchRoles([], ['createdTime' => 'DESC'], 0, PHP_INT_MAX, ['code', 'name']);

        return $this->createSuccessJsonResponse($roles);
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