<?php

use Phpmig\Migration\Migration;

class SmpRoleTable extends Migration
{

    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_role` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `name` varchar(32) NOT NULL COMMENT '权限名称',
            `code` varchar(32) NOT NULL COMMENT '权限代码',
            `data` text COMMENT '权限配置',
            `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
            `createdUserId` int(10) unsigned NOT NULL COMMENT '创建用户ID',
            `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '更新时间',
            PRIMARY KEY (`id`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8
          ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_role`");
    }
}