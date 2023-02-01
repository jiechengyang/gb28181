<?php

use Phpmig\Migration\Migration;

class VideoChannlsAddAutoLiveField extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_video_channels` 
ADD COLUMN `auto_live` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否为自动直播' AFTER `close_live`;");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_video_channels` DROP COLUMN `auto_live` ;");
    }
}
