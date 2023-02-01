<?php

namespace Biz\IpBlacklist\Dao;

use Codeages\Biz\Framework\Dao\AdvancedDaoInterface;

interface IpBlacklistDao extends AdvancedDaoInterface
{
    public function getByIpAndType($ip, $type);
}
