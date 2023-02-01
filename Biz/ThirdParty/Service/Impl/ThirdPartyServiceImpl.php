<?php


namespace Biz\ThirdParty\Service\Impl;


use Biz\BaseService;
use Biz\ThirdParty\Dao\ThirdPartyDao;
use Biz\ThirdParty\Exception\ThirdPartyException;
use Biz\ThirdParty\Service\ThirdPartyService;
use support\utils\ArrayToolkit;
use support\utils\StringToolkit;

class ThirdPartyServiceImpl extends BaseService implements ThirdPartyService
{

    public function findThirdPartiesByIds($ids)
    {
        if (empty($ids)) {
            return [];
        }

        $parties = $this->getThirdPartyDao()->getThirdPartiesByIds($ids);

        return ArrayToolkit::index($parties, 'id');
    }

    public function countThirdParties(array $conditions)
    {
        return $this->getThirdPartyDao()->count($conditions);
    }

    public function searchThirdParties(array $conditions, array $orderBy, $start, $limit, $columns = [])
    {
        return $this->getThirdPartyDao()->search($conditions, $orderBy, $start, $limit, $columns);
    }

    public function createThirdParty($fields)
    {
        $this->validateThirdPartyFields($fields);
        $party = $this->getThirdPartyByAppName($fields['partner_name']);
        if (!empty($party)) {
            $this->createNewException(ThirdPartyException::ALREADY_EXIST_PARTNER_NAME());
        }
        
        if (empty($fields['expired_time'])) {
            $fields['expired_time'] = 0;
        } else {
            $fields['expired_time'] = strtotime($fields['expired_time']);
        }

        $fields['partner_key'] = $this->generateAppKey();
        $fields['partner_sceret'] = $this->generateAppSceret();

        $thirdParty = $this->getThirdPartyDao()->create($fields);

        return $thirdParty;

    }

    public function updateThirdParty($id, $fields)
    {
        if (empty($fields['expired_time'])) {
            $fields['expired_time'] = 0;
        } else {
            $fields['expired_time'] = strtotime($fields['expired_time']);
        }
        empty($fields['partner_key']) && $fields['partner_key'] = $this->generateAppKey();
        empty($fields['partner_sceret']) && $fields['partner_sceret'] = $this->generateAppSceret();

        $thirdParty = $this->getThirdPartyDao()->update($id, $fields);

        return $thirdParty;
    }

    public function deleteThirdParty($id)
    {
        return $this->getThirdPartyDao()->delete($id);
    }

    public function lockThirdParty($id)
    {
        if (empty($id)) {
            return false;
        }

        return $this->getThirdPartyDao()->update($id, [
            'locked' => 1,
            'lock_deadline' => time()
        ]);
    }

    public function unlockThirdParty($id)
    {
        if (empty($id)) {
            return false;
        }
        
        return $this->getThirdPartyDao()->update($id, [
            'locked' => 0,
            'lock_deadline' => 0
        ]);
    }

    public function lockThirdParties($ids)
    {
        if (empty($ids)) {
            return false;
        }
        
        return $this->getThirdPartyDao()->update(['ids' => $ids], [
            'locked' => 1,
            'lock_deadline' => time()
        ]);
    }

    public function unlockThirdParties($ids)
    {
        if (empty($ids)) {
            return false;
        }
        
        return $this->getThirdPartyDao()->update(['ids' => $ids], [
            'locked' => 0,
            'lock_deadline' => 0
        ]);
    }

    public function getThirdParty($id)
    {
        return $this->getThirdPartyDao()->get($id);
    }

    public function getThirdPartyByAppName(string $appName)
    {
        return $this->getThirdPartyDao()->getThirdPartnerByAppName($appName);
    }

    public function getThirdPartyByAppKey(string $appKey)
    {
        return $this->getThirdPartyDao()->getThirdPartnerByAppKey($appKey);
    }

    protected function generateAppKey()
    {
        $appKey = StringToolkit::generateRandomString(18);
        $thirdParty = $this->getThirdPartyByAppKey($appKey);

        if (empty($thirdParty)) {
            return $appKey;
        } else {
            return $this->generateAppKey();
        }
    }

    protected function generateAppSceret()
    {
        $appKey = StringToolkit::generateRandomString();
        $thirdParty = $this->getThirdPartyByAppKey($appKey);

        if (empty($thirdParty)) {
            return $appKey;
        } else {
            return $this->generateAppSceret();
        }
    }

    protected function validateThirdPartyFields($fields)
    {
        if (!ArrayToolkit::requireds($fields, ['partner_name'], true)) {
            throw $this->createInvalidArgumentException('Lack of required fields');
        }
    }

    /**
     * @return ThirdPartyDao
     */
    protected function getThirdPartyDao()
    {
        return $this->createDao('ThirdParty:ThirdPartyDao');
    }
}