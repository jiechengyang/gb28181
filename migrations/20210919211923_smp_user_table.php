<?php

use Phpmig\Migration\Migration;

class SmpUserTable extends Migration
{
    /**
     * Do the migration
     */
    public function up()
    {
        $sql = "CREATE TABLE `smp_user` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `email` varchar(128) NOT NULL COMMENT '用户邮箱',
  `verifiedMobile` varchar(32) NOT NULL DEFAULT '',
  `password` varchar(64) NOT NULL COMMENT '用户密码',
  `salt` varchar(32) NOT NULL COMMENT '密码SALT',
  `payPassword` varchar(64) NOT NULL DEFAULT '' COMMENT '支付密码',
  `payPasswordSalt` varchar(64) NOT NULL DEFAULT '' COMMENT '支付密码Salt',
  `locale` varchar(20) DEFAULT NULL,
  `uri` varchar(64) NOT NULL DEFAULT '' COMMENT '用户URI',
  `nickname` varchar(64) NOT NULL COMMENT '用户名',
  `title` varchar(255) NOT NULL DEFAULT '' COMMENT '头衔',
  `tags` varchar(255) NOT NULL DEFAULT '' COMMENT '标签',
  `type` varchar(32) NOT NULL COMMENT 'default默认为网站注册, weibo新浪微薄登录',
  `point` int(11) NOT NULL DEFAULT '0' COMMENT '积分',
  `smallAvatar` varchar(255) NOT NULL DEFAULT '' COMMENT '小头像',
  `mediumAvatar` varchar(255) NOT NULL DEFAULT '' COMMENT '中头像',
  `largeAvatar` varchar(255) NOT NULL DEFAULT '' COMMENT '大头像',
  `emailVerified` tinyint(1) NOT NULL DEFAULT '0' COMMENT '邮箱是否为已验证',
  `setup` tinyint(4) NOT NULL DEFAULT '1' COMMENT '是否初始化设置的，未初始化的可以设置邮箱、用户名。',
  `roles` varchar(255) NOT NULL COMMENT '用户角色',
  `promoted` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否为推荐',
  `promotedSeq` int(10) unsigned NOT NULL DEFAULT '0',
  `promotedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '推荐时间',
  `locked` tinyint(3) unsigned NOT NULL DEFAULT '0' COMMENT '是否被禁止',
  `lockDeadline` int(10) NOT NULL DEFAULT '0' COMMENT '帐号锁定期限',
  `consecutivePasswordErrorTimes` int(11) NOT NULL DEFAULT '0' COMMENT '帐号密码错误次数',
  `lastPasswordFailTime` int(10) NOT NULL DEFAULT '0',
  `loginTime` int(11) NOT NULL DEFAULT '0' COMMENT '最后登录时间',
  `loginIp` varchar(64) NOT NULL DEFAULT '' COMMENT '最后登录IP',
  `loginSessionId` varchar(255) NOT NULL DEFAULT '' COMMENT '最后登录会话ID',
  `approvalTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '实名认证时间',
  `approvalStatus` enum('unapprove','approving','approved','approve_fail') NOT NULL DEFAULT 'unapprove' COMMENT '实名认证状态',
  `createdIp` varchar(64) NOT NULL DEFAULT '' COMMENT '注册IP',
  `createdTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '注册时间',
  `updatedTime` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '最后更新时间',
  `inviteCode` varchar(255) DEFAULT NULL COMMENT '邀请码',
  `orgId` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '组织机构ID',
  `orgCode` varchar(255) NOT NULL DEFAULT '1.' COMMENT '组织机构内部编码',
  `registeredWay` varchar(64) NOT NULL DEFAULT '' COMMENT '注册设备来源(web/ios/android)',
  `uuid` varchar(255) NOT NULL DEFAULT '' COMMENT '用户uuid',
  `passwordInit` tinyint(1) NOT NULL DEFAULT '1' COMMENT '初始化密码',
  `registerVisitId` varchar(64) NOT NULL DEFAULT '',
  `destroyed` tinyint(1) unsigned NOT NULL DEFAULT '0' COMMENT '是否注销',
  `passwordChanged` tinyint(1) NOT NULL DEFAULT '0' COMMENT '是否修改密码',
  `third_party_id` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '所属合作方',
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `nickname` (`nickname`),
  UNIQUE KEY `uuid` (`uuid`),
  KEY `updatedTime` (`updatedTime`),
  KEY `user_type_index` (`type`),
  KEY `verifiedMobile` (`verifiedMobile`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;";
        $container = $this->getContainer();
        $container['db']->exec($sql);
    }

    /**
     * Undo the migration
     */
    public function down()
    {
        $container = $this->getContainer();
        $container['db']->exec("DROP TABLE IF EXISTS `smp_user`");
    }
}
