<?php

use Phpmig\Migration\Migration;

class SmpIpBlacklistTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_ip_blacklist` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `ip` varchar(32) NOT NULL,
            `type` enum('failed','banned') NOT NULL COMMENT '禁用类型',
            `counter` int(10) unsigned NOT NULL DEFAULT '0',
            `expiredTime` int(10) unsigned NOT NULL DEFAULT '0',
            `createdTime` int(10) unsigned NOT NULL DEFAULT '0',
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
        $container['db']->exec("DROP TABLE IF EXISTS `smp_ip_blacklist`");
    }
}
