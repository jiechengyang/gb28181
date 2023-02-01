<?php

namespace Biz\Role\Dao;

use Codeages\Biz\Framework\Dao\AdvancedDaoInterface;

interface RoleDao extends AdvancedDaoInterface
{
    public function getByCode($code);

    public function getByName($name);

    public function findByCodes($codes);
}
