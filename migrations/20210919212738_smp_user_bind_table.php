<?php

use Phpmig\Migration\Migration;

class SmpUserBindTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_user_bind` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '用户绑定ID',
            `type` varchar(64) NOT NULL COMMENT '用户绑定类型',
            `fromId` varchar(32) NOT NULL COMMENT '来源方用户ID',
            `toId` int(10) unsigned NOT NULL COMMENT '被绑定的用户ID',
            `token` varchar(255) NOT NULL DEFAULT '' COMMENT 'oauth token',
            `refreshToken` varchar(255) NOT NULL DEFAULT '' COMMENT 'oauth refresh token',
            `expiredTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'token过期时间',
            `createdTime` int(10) unsigned NOT NULL COMMENT '绑定时间',
            PRIMARY KEY (`id`),
            UNIQUE KEY `type` (`type`,`fromId`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8
          ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_user_bind`");
    }
}
