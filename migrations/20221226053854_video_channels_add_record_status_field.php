<?php

use Phpmig\Migration\Migration;

class VideoChannelsAddRecordStatusField extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_video_channels` 
ADD COLUMN `record_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '录像状态（0=未录像，1=录像中）' AFTER `record_plan_id`;");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_video_channels`  DROP COLUMN `record_status`; ");
    }
}
