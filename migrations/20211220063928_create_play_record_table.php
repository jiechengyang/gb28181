<?php

use Phpmig\Migration\Migration;

class CreatePlayRecordTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_play_record` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `vc_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT 'videoChannel Id',
  `code` varchar(64) NOT NULL DEFAULT '' COMMENT '设备编码',
  `media_server_id` varchar(64) NOT NULL DEFAULT '' COMMENT '流媒体服务id',
  `client_ip` varchar(18) NOT NULL COMMENT '播放者ip',
  `player_id` varchar(64) NOT NULL DEFAULT '' COMMENT '播放id（第三方）',
  `server_port` int(6) NOT NULL COMMENT 'rtp端口号',
  `params` varchar(500) DEFAULT NULL COMMENT '参数',
  `startTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '开始播放时间',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '添加时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_play_record`");
    }
}
