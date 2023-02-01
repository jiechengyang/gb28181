<?php


namespace Biz\ThirdParty\Service;


interface ThirdPartyService
{

    public function createThirdParty($fields);

    public function updateThirdParty($id, $fields);

    public function deleteThirdParty($id);

    /**
     * @param $id
     * @return array|null
     */
    public function getThirdParty($id);

    /**
     * @param string $appName
     * @return array|null
     */
    public function getThirdPartyByAppName(string $appName);

    /**
     * @param string $appKey
     * @return array|null
     */
    public function getThirdPartyByAppKey(string $appKey);

    /**
     * 
     *
     * @param array $conditions
     * @return int
     */
    public function countThirdParties(array $conditions);

    /**
     * 
     *
     * @param array $conditions
     * @param array $orderBy
     * @param [type] $start
     * @param [type] $limit
     * @param array $columns
     * @return array[]
     */
    public function searchThirdParties(array $conditions, array $orderBy, $start, $limit, $columns = []);

    public function lockThirdParty($id);

    public function unlockThirdParty($id);

    public function lockThirdParties($ids);

    public function unlockThirdParties($ids);

    public function findThirdPartiesByIds($ids);
}