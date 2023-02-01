<?php

use Phpmig\Migration\Migration;

class VideoChannelsAddLocalIpV4 extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_video_channels` 
ADD COLUMN `local_ip_v4` varchar(32) NOT NULL DEFAULT  '' COMMENT '本地ip地址' AFTER `locked`;");

    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_video_channels` DROP COLUMN `local_ip_v4` ;");
    }
}
