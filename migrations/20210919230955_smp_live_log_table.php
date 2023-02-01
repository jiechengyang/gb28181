<?php

use Phpmig\Migration\Migration;

class SmpLiveLogTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_live_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
  `status` tinyint(2) NOT NULL DEFAULT '0' COMMENT '状态(-1:失败;0:未知;1:成功)',
  `parter_key` varchar(32) NOT NULL DEFAULT '' COMMENT '合作方key',
  `live_provider` varchar(32) NOT NULL DEFAULT '' COMMENT '云监控提供商',
  `url` varchar(1024) NOT NULL DEFAULT '' COMMENT '播放地址',
  `expireTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '播放地址有效期,0表示不过期',
  `request_ip` varchar(32) NOT NULL DEFAULT '' COMMENT '请求ip',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_live_log`");
    }
}
