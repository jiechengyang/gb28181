<?php

use Phpmig\Migration\Migration;

class AddVideoChannelsRecordPlanIdField extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_video_channels` 
ADD COLUMN `record_plan_id` int(10) NOT NULL DEFAULT 0 COMMENT '录像计划id' AFTER `auto_live`;");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_video_channels`  DROP COLUMN `record_plan_id`; ");
    }
}
