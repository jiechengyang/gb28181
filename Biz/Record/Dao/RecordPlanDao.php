<?php

namespace Biz\Record\Dao;

use Codeages\Biz\Framework\Dao\AdvancedDaoInterface;

interface RecordPlanDao extends AdvancedDaoInterface
{
    public function getByName($name);

    public function getByNameAndPartnerId($name, $partnerId);
}
