<?php

namespace Biz\Role\Service\Impl;

use Biz\BaseService;
use support\utils\ArrayToolkit;
use Biz\Role\Exception\RoleException;
use Biz\Common\CommonException;
use Biz\Role\Service\RoleService;
use Biz\Role\Dao\RoleDao;
use Biz\Setting\Service\SettingService;
use Biz\SystemLog\Service\SystemLogService;
use Biz\User\Service\UserService;

class RoleServiceImpl extends BaseService implements RoleService
{

    public function getRole($id)
    {
        return $this->getRoleDao()->get($id);
    }

    public function getRoleByCode($code)
    {
        return $this->getRoleDao()->getByCode($code);
    }

    public function createRole($role)
    {
        $role['createdTime'] = time();
        $user = $this->getCurrentUser();
        $role['createdUserId'] = $user['id'];
        $role = ArrayToolkit::parts($role, ['name', 'code', 'data', 'data_v2', 'createdTime', 'createdUserId']);

        if (!ArrayToolkit::requireds($role, ['name', 'code'])) {
            $this->createNewException(CommonException::ERROR_PARAMETER_MISSING());
        }

        if (!preg_match('/(^(?![^0-9a-zA-Z]+$))(?![0-9]+$).+/', $role['code'])) {
            $this->createNewException(RoleException::CODE_NOT_ALLL_DIGITAL());
        }

        return $this->getRoleDao()->create($role);
    }

    public function updateRole($id, array $fields)
    {
        $this->checkChangeRole($id);
        $fields = ArrayToolkit::parts($fields, ['name', 'code', 'data', 'data_v2']);

        if (isset($fields['code'])) {
            unset($fields['code']);
        }

        $fields['updatedTime'] = time();
        $role = $this->getRoleDao()->update($id, $fields);

        return $role;
    }

    public function deleteRole($id)
    {
        $role = $this->checkChangeRole($id);
        if (!empty($role)) {
            $this->getRoleDao()->delete($id);
        }
    }

    public function searchRoles($conditions, $sort, $start, $limit, $columns = [])
    {
        $conditions = $this->prepareSearchConditions($conditions);

        switch ($sort) {
            case 'created':
                $sort = ['createdTime' => 'DESC'];
                break;
            case 'createdByAsc':
                $sort = ['createdTime' => 'ASC'];
                break;

            default:
                $sort = ['createdTime' => 'DESC'];
                break;
        }

        return $this->getRoleDao()->search($conditions, $sort, $start, $limit, $columns);
    }

    public function searchRolesCount($conditions)
    {
        $conditions = $this->prepareSearchConditions($conditions);

        return $this->getRoleDao()->count($conditions);
    }

    public function findRolesByCodes(array $codes)
    {
        if (empty($codes)) {
            return [];
        }

        return $this->getRoleDao()->findByCodes($codes);
    }

    /**
     * @param $tree '后台menus树结构数组'
     * @param array $permissions '分割散的树节点Array'
     *
     * @return array
     *
     * 分割树结构各个节点形成array(array('code'=>xxx,'parent'=>xxx))二维数组
     */
    public function splitRolesTreeNode($tree, &$permissions = [])
    {
        foreach ($tree as &$child) {
            $permissions[$child['code']] = [
                'code' => $child['code'],
                'parent' => isset($child['parent']) ? $child['parent'] : null,
            ];
            if (isset($child['children'])) {
                $child['children'] = $this->splitRolesTreeNode($child['children'], $permissions);
            }
        }

        return $permissions;
    }

    /**
     * @param $code '树节点的code'
     * @param $permissions '分割散的树节点Array, splitRolesTreeNode'
     * @param array $parentCodes
     *
     * @return array 返回传入节点所有的父级节点code
     */
    public function getParentRoleCodeArray($code, $nodes, &$parentCodes = [])
    {
        if (!empty($nodes[$code]) && !empty($nodes[$code]['parent'])) {
            $parentCodes[] = $nodes[$code]['parent'];
            $parentCodes = $this->getParentRoleCodeArray($nodes[$code]['parent'], $nodes, $parentCodes);
        }

        return $parentCodes;
    }

    public function refreshRoles()
    {
        $permissions = PermissionBuilder::instance()->loadPermissionsFromAllConfig();
        $tree = Tree::buildWithArray($permissions, null, 'code', 'parent');
        //获取老后台权限menus
        $roles = $this->getAdminRoles($tree);
        //获取新后台权限menus
        $v2Roles = $this->getAdminV2Roles($tree);
        foreach ($roles as $key => $value) {
            $userRole = $this->getRoleDao()->getByCode($key);

            if (empty($userRole)) {
                $this->initCreateRole($key, array_values($value), array_values($v2Roles[$key]));
            } else {
                $this->getRoleDao()->update($userRole['id'], ['data' => array_values($value), 'data_v2' => array_values($v2Roles[$key])]);
            }
        }
    }

    /**
     * @param $tree
     *
     * @return array
     *
     * 获取老后台权限menus
     */
    protected function getAdminRoles($tree)
    {
        $getAdminRoles = $tree->find(function ($tree) {
            return 'admin' === $tree->data['code'];
        });
        $adminRoles = $getAdminRoles->column('code');
        $getWebRoles = $tree->find(function ($tree) {
            return 'web' === $tree->data['code'];
        });
        $webRoles = $getWebRoles->column('code');
        $adminForbidParentRoles = [
            'admin_user_avatar',
            'admin_user_change_password',
            'admin_my_cloud',
            'admin_cloud_video_setting',
            'admin_edu_cloud_sms',
            'admin_edu_cloud_search_setting',
            'admin_setting_cloud_attachment',
            'admin_setting_cloud',
            'admin_system',
        ];
        $adminForbidRoles = $this->getAllForbidRoles($getAdminRoles, $adminForbidParentRoles);
        $superAdminRoles = array_merge($adminRoles, $webRoles);

        return [
            'ROLE_PARTER_ADMIN' => array_diff($superAdminRoles, $adminForbidRoles),
            'ROLE_SUPER_ADMIN' => $superAdminRoles,
        ];
    }

    /**
     * @param $tree
     *
     * @return array
     *
     * 获取新后台权限menus
     */
    protected function getAdminV2Roles($tree)
    {
        $getAdminV2Roles = $tree->find(function ($tree) {
            return 'admin_v2' === $tree->data['code'];
        });
        $adminV2Roles = $getAdminV2Roles->column('code');

        $getWebRoles = $tree->find(function ($tree) {
            return 'web' === $tree->data['code'];
        });
        $webRoles = $getWebRoles->column('code');

        $adminV2ForbidParentRoles = [
            'admin_v2_user_avatar',
            'admin_v2_user_change_password',
            'admin_v2_user_change_nickname',
            'admin_v2_my_cloud',
            'admin_v2_cloud_video',
            'admin_v2_cloud_sms',
            'admin_v2_cloud_search',
            'admin_v2_cloud_attachment_setting',
            'admin_v2_setting_cloud',
            'admin_v2_system',
        ];

        $adminV2ForbidRoles = $this->getAllForbidRoles($getAdminV2Roles, $adminV2ForbidParentRoles);
        $superAdminV2Roles = array_merge($adminV2Roles, $webRoles);

        return [
            'ROLE_PARTER_ADMIN' => array_diff($superAdminV2Roles, $adminV2ForbidRoles),
            'ROLE_SUPER_ADMIN' => $superAdminV2Roles,
        ];
    }

    private function getDefaultTeacherAdminRole()
    {
        return [
            'admin_v2', 'admin_v2_teach', 'admin_v2_course_group', 'admin_v2_multi_class', 'admin_v2_multi_class_manage', 'admin_v2_course_show', 'admin_v2_course_manage', 'admin_v2_course_content_manage', 'admin_v2_course_add', 'admin_v2_course_guest_member_preview', 'admin_v2_course_set_close', 'admin_v2_course_set_clone', 'admin_v2_course_set_publish', 'admin_v2_course_set_delete', 'admin_v2_course_set_remove', 'admin_v2_course_set_data',
        ];
    }

    /**
     * @param $tree
     * @param $forbidRoles
     *
     * @return array
     *
     * '根据admin|admin_v2各自的权限树和要过滤的权限的部分节点返回完整的被过滤的权限code'
     */
    protected function getAllForbidRoles($tree, $forbidRoles)
    {
        $adminForbidRoles = [];
        foreach ($forbidRoles as $forbidRole) {
            $adminRole = $tree->find(function ($tree) use ($forbidRole) {
                return $tree->data['code'] === $forbidRole;
            });

            if (is_null($adminRole)) {
                continue;
            }

            $adminForbidRoles = array_merge($adminRole->column('code'), $adminForbidRoles);
        }

        return $adminForbidRoles;
    }

    private function initCreateRole($code, $role, $v2Role)
    {
        $userRoles = [
            'ROLE_SUPER_ADMIN' => ['name' => '超级管理员', 'code' => 'ROLE_SUPER_ADMIN'],
            'ROLE_PARTER_ADMIN' => ['name' => '合作方', 'code' => 'ROLE_PARTER_ADMIN'],
        ];
        $userRole = $userRoles[$code];

        $userRole['data'] = $role;
        $userRole['data_v2'] = $v2Role;
        $userRole['createdTime'] = time();
        $userRole['createdUserId'] = $this->getCurrentUser()->getId();
        $this->getLogService()->info('role', 'init_create_role', '初始化四个角色"' . $userRole['name'] . '"', $userRole);

        return $this->getRoleDao()->create($userRole);
    }

    private function checkChangeRole($id)
    {
        $role = $this->getRoleDao()->get($id);
        $notUpdateRoles = ['ROLE_SUPER_ADMIN', 'ROLE_PARTER_ADMIN'];
        if (in_array($role['code'], $notUpdateRoles)) {
            $this->createNewException(RoleException::FORBIDDEN_MODIFY());
        }

        return $role;
    }

    protected function prepareSearchConditions($conditions)
    {
        if (!empty($conditions['nextExcutedStartTime']) && !empty($conditions['nextExcutedEndTime'])) {
            $conditions['nextExcutedStartTime'] = strtotime($conditions['nextExcutedStartTime']);
            $conditions['nextExcutedEndTime'] = strtotime($conditions['nextExcutedEndTime']);
        } else {
            unset($conditions['nextExcutedStartTime']);
            unset($conditions['nextExcutedEndTime']);
        }

        if (empty($conditions['cycle'])) {
            unset($conditions['cycle']);
        }

        return $conditions;
    }

    /**
     * 主要用于主程序和所有插件在主程序8.5.0版本升级处理自定义角色的data_v2，根据原有权限配置data找到对应的v2的menus
     */
    public function upgradeRoleDataV2()
    {
        $roles = $this->searchRoles(['excludeCodes' => ['ROLE_PARTER_ADMIN', 'ROLE_SUPER_ADMIN']], [], 0, PHP_INT_MAX);

        foreach ($roles as &$role) {
            $role['data_v2'] = $this->getAdminV2Permissions($role['data']);
            $this->updateRole($role['id'], $role);
        }
    }

    protected function loadPermissionsFromAllConfig($type = 'admin')
    {
    }

    protected function getPermissionConfig($type = 'admin')
    {
    }

    /**
     *
     * 根据role的data的menus获取对应的admin_v2的menus
     * @param $roleData
     * @return array
     */
    protected function getAdminV2Permissions($roleData)
    {
    }

    /**
     * @return RoleDao
     */
    protected function getRoleDao()
    {
        return $this->createDao('Role:RoleDao');

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
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }
}
