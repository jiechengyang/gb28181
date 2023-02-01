<?php

use Phpmig\Migration\Migration;

class SmpRatelimitTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_ratelimit` (
            `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `_key` varchar(128) NOT NULL,
            `data` varchar(32) NOT NULL,
            `deadline` int(10) unsigned NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `_key` (`_key`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_ratelimit`");
    }
}
