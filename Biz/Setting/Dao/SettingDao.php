<?php

namespace Biz\Setting\Dao;

use Codeages\Biz\Framework\Dao\AdvancedDaoInterface;

interface SettingDao extends AdvancedDaoInterface
{
    public function findAll();

    public function deleteByName($name);

    public function deleteByNamespaceAndName($namespace, $name);

    /**
     * @param $name
     * @param $namespace
     * @return array|null
     */
    public function findByNameAndNamespace($name, $namespace);
}
