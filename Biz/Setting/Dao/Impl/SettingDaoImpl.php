<?php

namespace Biz\Setting\Dao\Impl;

use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;
use Biz\Setting\Dao\SettingDao;

class SettingDaoImpl extends AdvancedDaoImpl implements SettingDao 
{

    protected $table = 'smp_setting';

    /**
     * @param $name
     * @param $namespace
     * @return array|null
     */
    public function findByNameAndNamespace($name, $namespace)
    {
        return $this->getByFields([
            'name' => $name,
            'namespace' => $namespace,
        ]);
    }

    public function findAll()
    {
        $sql = "SELECT * FROM {$this->table}";

        return $this->db()->fetchAll($sql, array());
    }

    public function deleteByName($name)
    {
        return $this->db()->delete($this->table, array('name' => $name));
    }

    public function deleteByNamespaceAndName($namespace, $name)
    {
        return $this->db()->delete($this->table, array('namespace' => $namespace, 'name' => $name));
    }

    public function declares()
    {
        return [
            'conditions' => [
                'name = :name',
                'namespace = :namespace',
            ],
        ];
    }
}
