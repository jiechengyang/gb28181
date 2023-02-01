<?php

use Phpmig\Migration\Migration;

class VideoChannelsAddCloseLiveField extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_video_channels` 
ADD COLUMN `close_live` tinyint(1) UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否关闭直播' AFTER `local_ip_v4`;");

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_video_channels` DROP COLUMN `close_live` ;");
    }
}
