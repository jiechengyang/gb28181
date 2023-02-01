<?php

use Phpmig\Migration\Migration;

class CreateSmpDeviceRegisterLog extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_device_register_log` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `deviceId` varchar(32) NOT NULL DEFAULT '' COMMENT '设备序列号',
  `isReady` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否就绪(0:否；1：是)',
  `data` longblob COMMENT '注册信息',
  `registerTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册时间',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `type` enum('unRegistered','registered') NOT NULL DEFAULT 'registered' COMMENT '类型(registered:注册；unRegistered:注销)',
  `ipAddress` varchar(32) NOT NULL DEFAULT '' COMMENT 'ip地址',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_device_register_log`");
    }
}
