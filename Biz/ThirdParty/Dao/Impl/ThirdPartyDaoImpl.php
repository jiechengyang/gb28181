<?php


namespace Biz\ThirdParty\Dao\Impl;


use Biz\ThirdParty\Dao\ThirdPartyDao;
use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;

class ThirdPartyDaoImpl  extends AdvancedDaoImpl implements ThirdPartyDao
{
    protected $table = 'smp_third_party';

    public function getThirdPartiesByIds($ids) 
    {
        return $this->findInField('id', $ids);
    }

    public function getThirdPartnerByAppName($appName)
    {
        return $this->getByFields(['partner_name' => $appName]);
    }

    public function getThirdPartnerByAppKey($appKey)
    {
        return $this->getByFields(['partner_key' => $appKey]);
    }

    public function declares()
    {
        return [
            'serializes' => [
                'params' => 'json',
                'live_providers' => 'delimiter',
            ],
            'orderbys' => [
                'id',
                'createdTime',
                'updatedTime',
                'locked',
            ],
            'timestamps' => [
                'createdTime',
                'updatedTime',
            ],
            'conditions' => [
                'partner_name = :partnerName',
                'partner_name LIKE :partnerNameLike',
                'partner_key = :partnerKey',
                'partner_sceret = :partnerSceret',
                'id =: id',
                'createdTime >= :startTime',
                'createdTime <= :endTime',
                'locked = :locked',
                'id IN ( :thirdIds)',
                'id IN ( :ids)',
                'id NOT IN ( :excludeIds )',
            ],
        ];
    }
}