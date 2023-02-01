<?php

use Phpmig\Migration\Migration;

class SmpThirdPartyTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $container = $this->getContainer();
        $container['db']->exec("CREATE TABLE `smp_third_party` (
            `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
            `partner_name` varchar(255) NOT NULL DEFAULT '' COMMENT '合作方名称',
            `partner_key` varchar(32) NOT NULL COMMENT '合作方key',
            `partner_sceret` varchar(64) NOT NULL COMMENT '合作方密钥',
            `live_providers` varchar(64) NOT NULL DEFAULT 'BLive' COMMENT '云监控提供平台',
            `params` blob COMMENT '云监控平台参数',
            `locked` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否被锁定',
            `lock_deadline` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '被锁定时间',
            `server_ip` varchar(18) NOT NULL DEFAULT '127.0.0.1' COMMENT '服务ip',
            `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '创建时间',
            `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '修改时间',
            PRIMARY KEY (`id`),
            UNIQUE KEY `partner_name_unique` (`partner_name`),
            UNIQUE KEY `partner_key_unique` (`partner_key`),
            UNIQUE KEY `partner_seret_unique` (`partner_sceret`)
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_third_party`");
    }
}
