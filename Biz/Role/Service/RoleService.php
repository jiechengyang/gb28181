<?php

namespace Biz\Role\Service;

interface RoleService
{
    public function getRole($id);

    public function getRoleByCode($code);

    public function findRolesByCodes(array $codes);

    /**
     * @param $role
     *
     * @return mixed
     */
    public function createRole($role);

    /**
     * @param $id
     * @param array $fiedls
     *
     * @return mixed
     */
    public function updateRole($id, array $fiedls);

    /**
     * @param $id
     *
     * @return mixed
     */
    public function deleteRole($id);

    public function searchRoles($conditions, $sort, $start, $limit, $columns = []);

    public function searchRolesCount($conditions);

    public function refreshRoles();

    public function splitRolesTreeNode($tree, &$permissions = array());

    public function getParentRoleCodeArray($code, $nodes, &$parentCodes = array());

}
