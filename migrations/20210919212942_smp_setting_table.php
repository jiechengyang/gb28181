<?php

use Phpmig\Migration\Migration;

class SmpSettingTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_setting` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '系统设置ID',
            `name` varchar(64) NOT NULL DEFAULT '' COMMENT '系统设置名',
            `value` longblob COMMENT '系统设置值',
            `namespace` varchar(255) NOT NULL DEFAULT 'default',
            PRIMARY KEY (`id`),
            UNIQUE KEY `name` (`name`,`namespace`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8
          
          ");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_setting`");
    }
}
