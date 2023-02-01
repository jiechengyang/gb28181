<?php

use Phpmig\Migration\Migration;

class CreateRecordPlanTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_record_plan` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `partner_id` int(10) NOT NULL DEFAULT '0' COMMENT '所属合作方',
  `name` varchar(255) NOT NULL DEFAULT '' COMMENT '录制计划名称',
  `status` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否启用该录制计划(1=启用；0=禁用)',
  `remark` text COMMENT '录制计划的描述',
  `limit_space` bigint(20) NOT NULL DEFAULT '0' COMMENT '录制占用空间限制（Byte）,最大录制到某个值后做相应处理',
  `limit_days` int(6) NOT NULL DEFAULT '0' COMMENT '录制占用天数限制,最大录制到某个值后做相应处理',
  `over_step_plan` enum('stopDvr','delFile') NOT NULL DEFAULT 'delFile' COMMENT '超出天数限制后执行动作\n',
  `created_time` int(11) NOT NULL DEFAULT '0' COMMENT '创建时间',
  `updated_time` int(11) NOT NULL DEFAULT '0' COMMENT '修改时间',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 COMMENT='录制计划';");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_record_plan`");
    }
}
