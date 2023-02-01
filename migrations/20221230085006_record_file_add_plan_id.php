<?php

use Phpmig\Migration\Migration;

class RecordFileAddPlanId extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_record_file` 
ADD COLUMN `plan_id` int(10) NOT NULL DEFAULT 0 COMMENT '录制计划id' AFTER `updated_time`;");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_record_file`  DROP COLUMN `plan_id`; ");
    }
}
