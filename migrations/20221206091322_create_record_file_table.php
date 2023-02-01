<?php

use Phpmig\Migration\Migration;

class CreateRecordFileTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_record_file` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键id',
  `main_id` varchar(64) NOT NULL DEFAULT '' COMMENT '设备唯一id',
  `media_server_id` varchar(64) NOT NULL COMMENT '流媒体服务器ID',
  `media_server_ip` varchar(64) NOT NULL COMMENT '流媒体服务器IP地址',
  `channel_id` varchar(64) NOT NULL COMMENT 'GB21818设备通道ID',
  `channel_name` varchar(255) NOT NULL COMMENT '通道名称',
  `device_id` varchar(64) NOT NULL COMMENT 'GB28181设备ID',
  `video_src_url` varchar(500) DEFAULT '' COMMENT '非gb28181设备的视频流源地址',
  `start_time` int(11) unsigned NOT NULL COMMENT '文件的开始时间',
  `end_time` int(11) NOT NULL COMMENT '文件的结束时间',
  `duration` int(10) DEFAULT NULL COMMENT '文件的时长(单位:s)',
  `video_path` varchar(255) DEFAULT NULL COMMENT '文件的所在位置',
  `file_size` bigint(20) DEFAULT '0' COMMENT '文件大小',
  `vhost` varchar(64) DEFAULT '' COMMENT 'vhost',
  `stream_id` varchar(64) DEFAULT '' COMMENT 'stream',
  `app` varchar(32) DEFAULT NULL COMMENT 'media server 流应用名',
  `download_url` varchar(255) DEFAULT '' COMMENT '文件下载与播放地址',
  `is_undo` tinyint(1) DEFAULT NULL COMMENT '是否可撤销删除操作(0:否，1：是）',
  `record_date` varchar(20) NOT NULL DEFAULT '' COMMENT '记录日期',
  `deleted_time` int(11) NOT NULL DEFAULT '0' COMMENT '是否删除',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '更新时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='录制文件实例';");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_record_file`");
    }
}
