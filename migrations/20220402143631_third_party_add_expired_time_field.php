<?php

use Phpmig\Migration\Migration;

class ThirdPartyAddExpiredTimeField extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_third_party` 
        ADD COLUMN `expired_time` int(10) NOT NULL COMMENT '有效期(0:无限制)' AFTER `lock_deadline`;");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("ALTER TABLE `smp_third_party` DROP COLUMN  `expired_time`");
    }
}
