<?php

use Phpmig\Migration\Migration;

class VideoChannelsAddFields extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_video_channels` 
ADD COLUMN `origin_main_id` varchar(32) NOT NULL DEFAULT '' COMMENT '关联设备编码(针对录像机播放花屏使用)' AFTER `lastOfflineTime`,
ADD COLUMN `locked` tinyint(10) NOT NULL DEFAULT 0 COMMENT '是否禁用(0:否，1：是）' AFTER `origin_main_id`;");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_video_channels` DROP COLUMN `origin_main_id`, DROP COLUMN `locked`;");
    }
}
