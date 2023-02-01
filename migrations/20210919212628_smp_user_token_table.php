<?php

use Phpmig\Migration\Migration;

class SmpUserTokenTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_user_token` (
                    `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'TOKEN编号',
                    `token` varchar(64) NOT NULL COMMENT 'TOKEN值',
                    `userId` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'TOKEN关联的用户ID',
                    `type` varchar(255) NOT NULL COMMENT 'TOKEN类型',
                    `data` text NOT NULL COMMENT 'TOKEN数据',
                    `times` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'TOKEN的校验次数限制(0表示不限制)',
                    `remainedTimes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'TOKE剩余校验次数',
                    `expiredTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'TOKEN过期时间',
                    `createdTime` int(10) unsigned NOT NULL COMMENT 'TOKEN创建时间',
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `token` (`token`(60))
                  ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_user_token`");
    }
}
