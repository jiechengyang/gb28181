<?php

use Phpmig\Migration\Migration;

class SmpVideoChannelsTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_video_channels` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `main_id` varchar(32) NOT NULL DEFAULT '' COMMENT '设备的唯一ID',
  `media_server_id` varchar(64) NOT NULL DEFAULT '' COMMENT '流媒体服务器ID',
  `vhost` varchar(64) NOT NULL DEFAULT '__defaultVhost__' COMMENT 'zlmediakit vhost',
  `app` varchar(64) NOT NULL DEFAULT '' COMMENT 'zlmediakit app',
  `channel_name` varchar(64) NOT NULL DEFAULT '' COMMENT '通道名称，整个系统唯一',
  `device_network_type` varchar(64) NOT NULL DEFAULT 'Fixed' COMMENT '设备的网络类型',
  `device_stream_type` varchar(64) NOT NULL DEFAULT 'GB28181' COMMENT '设备的流类型',
  `video_device_type` varchar(64) NOT NULL DEFAULT '' COMMENT '设备类型，IPC,NVR,DVR',
  `ip_v4_address` varchar(32) DEFAULT '' COMMENT '设备的ipv6地址',
  `ip_v6_address` varchar(64) NOT NULL DEFAULT '' COMMENT '设备的ipv4地址',
  `has_ptz` tinyint(1) NOT NULL DEFAULT '0' COMMENT '设备是否有云台控制',
  `device_id` varchar(24) NOT NULL DEFAULT '' COMMENT 'GB28181设备的SipDevice.DeviceId',
  `channel_id` varchar(24) DEFAULT '' COMMENT 'GB28181设备的SipChannel.DeviceId',
  `rtp_proto` enum('udp','tcp') NOT NULL DEFAULT 'udp' COMMENT 'Rtp设备推流方式',
  `default_rtp_port` int(5) unsigned NOT NULL DEFAULT '10000' COMMENT 'Rtp设备是否使用流媒体默认rtp端口，如10000端口',
  `enabled` tinyint(3) unsigned NOT NULL DEFAULT '1' COMMENT '是否启用(0:否,1:是)；默认否，需要调用激活接口',
  `method_by_get_stream` varchar(255) NOT NULL DEFAULT '' COMMENT '使用哪种方式拉取非rtp设备的流',
  `video_src_url` varchar(255) NOT NULL DEFAULT '' COMMENT '非Rtp设备的视频流源地址',
  `device_status` tinyint(1) NOT NULL COMMENT '设备状态(0：未知；-1：离线；1在线)',
  `parter_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '合作方ID',
  `recorder_id` int(10) NOT NULL DEFAULT '0' COMMENT '所属录像机',
  `dept_id` varchar(24) NOT NULL DEFAULT '' COMMENT '部门代码（上报上级使用）',
  `dept_name` varchar(64) NOT NULL DEFAULT '' COMMENT '部门名称',
  `parent_dept_id` varchar(24) NOT NULL DEFAULT '' COMMENT '上级部门代码',
  `parent_dept_name` varchar(64) NOT NULL DEFAULT '' COMMENT '上级部门名称',
  `createdTime` int(10) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatedTime` int(10) NOT NULL DEFAULT '0' COMMENT '修改时间',
  `lastOnlineTime` int(10) unsigned NOT NULL COMMENT '最新上线的时间',
  `lastOfflineTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后离线的时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_mainId` (`main_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_video_channels`");
    }
}
