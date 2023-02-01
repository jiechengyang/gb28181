<?php

use Phpmig\Migration\Migration;

class CreateSmpDeviceActiveLog extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_device_active_log` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deviceId` varchar(32) NOT NULL DEFAULT '' COMMENT '设备ID',
  `keepAliveTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '活跃时间',
  `lostTimes` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '心跳连续丢失次数',
  `createdTime` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `keepAliveTime_index` (`keepAliveTime`)
) ENGINE=InnoDB AUTO_INCREMENT=85 DEFAULT CHARSET=utf8;");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_device_active_log`");
    }
}
