<?php

use Phpmig\Migration\Migration;

class SmpUserOrgTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_user_org` (
            `id` int(10) NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `userId` int(10) unsigned NOT NULL COMMENT '用户ID',
            `orgId` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '组织机构id',
            `orgCode` varchar(255) NOT NULL DEFAULT '1.' COMMENT '组织机构内部编码',
            `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
            `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
            PRIMARY KEY (`id`),
            KEY `userId` (`userId`),
            KEY `orgCode` (`orgCode`),
            KEY `orgId` (`orgId`),
            KEY `idx_orgId_userId` (`orgId`,`userId`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='用户组织机构关系'
          ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_user_org`");
    }
}
