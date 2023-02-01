<?php

use Phpmig\Migration\Migration;

class CreateRecordPlanRangeTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_record_plan_range` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `record_plan_id` int(11) NOT NULL DEFAULT '0' COMMENT '计划任务表的主键',
  `week_day` varchar(16) NOT NULL DEFAULT '' COMMENT '星期n枚举',
  `start_time` varchar(32) NOT NULL COMMENT '录制开始时间(时分格式)',
  `end_time` varchar(32) NOT NULL DEFAULT '' COMMENT '录制结束时间',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='录制计划明细';");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_record_plan_range`");
    }
}
