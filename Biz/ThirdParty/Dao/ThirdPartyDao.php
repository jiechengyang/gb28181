<?php


namespace Biz\ThirdParty\Dao;


use Codeages\Biz\Framework\Dao\AdvancedDaoInterface;

interface ThirdPartyDao extends AdvancedDaoInterface
{
    /**
     * @param $appName
     * @return array|null
     */
    public function getThirdPartnerByAppName($appName);

    /**
     * @param $appKey
     * @return array|null
     */
    public function getThirdPartnerByAppKey($appKey);

    public function getThirdPartiesByIds($ids);
}