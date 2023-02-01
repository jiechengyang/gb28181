<?php

use Phpmig\Migration\Migration;

class CreateSmpVideoRecorderTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_video_recorder` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `device_name` varchar(255) NOT NULL DEFAULT '' COMMENT '设备名称',
  `device_id` varchar(32) NOT NULL DEFAULT '' COMMENT '设备ID',
  `device_sn` varchar(16) NOT NULL DEFAULT '' COMMENT '设备SN',
  `type_code` int(4) NOT NULL DEFAULT '0' COMMENT '编码类型',
  `manufacturer` varchar(200) NOT NULL DEFAULT '' COMMENT '制造商',
  `device_model` varchar(100) NOT NULL DEFAULT '' COMMENT '模型',
  `firmware` varchar(100) NOT NULL DEFAULT '' COMMENT '固件版本',
  `channel_num` int(6) unsigned NOT NULL DEFAULT '0' COMMENT '通道数',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '设备状态(0：未知；-1：离线；1在线)',
  `local_ip` char(32) NOT NULL DEFAULT '' COMMENT '本地ip',
  `local_sip_port` int(6) unsigned NOT NULL DEFAULT '5060' COMMENT 'sip端口号',
  `local_http_port` int(6) unsigned NOT NULL DEFAULT '80' COMMENT '本地http端口号',
  `local_server_port` int(10) unsigned NOT NULL DEFAULT '8000' COMMENT '本地服务端口号',
  `net_ip` char(32) NOT NULL DEFAULT '' COMMENT '公网ip',
  `net_sip_port` int(6) unsigned NOT NULL DEFAULT '5060' COMMENT '公网sip端口号',
  `net_http_port` int(6) unsigned NOT NULL DEFAULT '80' COMMENT '公网http端口号',
  `net_server_port` int(6) NOT NULL DEFAULT '8000' COMMENT '公网服务端口号',
  `username` varchar(32) NOT NULL DEFAULT '' COMMENT '用户名',
  `password` varchar(32) NOT NULL DEFAULT '' COMMENT '密码',
  `address` varchar(500) NOT NULL DEFAULT '' COMMENT '设备所在地',
  `parter_id` int(10) NOT NULL DEFAULT '0' COMMENT '合作方ID（0：未绑定）',
  `lastOnlineTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最新上线的时间',
  `lastOfflineTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后离线的时间',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatedTime` int(10) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8;");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_nvr`");
    }
}
