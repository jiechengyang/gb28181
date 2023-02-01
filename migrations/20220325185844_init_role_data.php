<?php

use Phpmig\Migration\Migration;

class InitRoleData extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("INSERT INTO `smp_role`(`name`, `code`, `data`, `createdTime`, `createdUserId`, `updatedTime`) VALUES ('合作方', 'ROLE_PARTER_ADMIN', '', 1632322205, 0, 0);");
        $container['db']->exec("INSERT INTO `smp_role`(`name`, `code`, `data`, `createdTime`, `createdUserId`, `updatedTime`) VALUES ('超级管理员', 'ROLE_SUPER_ADMIN', '',1632322205,0,0);");
    }

    /**
     * Undo the migration
     */
    public function down()
    {

    }
}
