<?php

namespace Biz\IpBlacklist\Dao\Impl;

use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;
use Biz\IpBlacklist\Dao\IpBlacklistDao;

class IpBlacklistDaoImpl extends AdvancedDaoImpl implements IpBlacklistDao 
{

    protected $table = 'smp_ip_blacklist';

    public function getByIpAndType($ip, $type)
    {
        return $this->getByFields(array('ip' => $ip, 'type' => $type));
    }

    public function declares()
    {
        return [
            'orderbys' => [
                'createdTime',
           ], 
            'conditions' => [
                'ip = :ip',
                'type = :type',
           ], 
            'timestamps' => [ 
                'createdTime',
           ], 
        ];
    } 
}
