<?php

namespace Biz\Setting\Service\Impl;

use Biz\BaseService;

use Biz\Setting\Service\SettingService;
use Biz\Setting\Dao\SettingDao;
use Webman\Config;

class SettingServiceImpl extends BaseService implements SettingService
{
    public function getAkServerConfig()
    {
        $config = [
            'api_url' => Config::get('app.ak_config.api_url'),
            'access_key' => Config::get('app.ak_config.access_key'),
            'zlmediakit_api' => Config::get('app.ak_config.zlmediakit_api'),
            'zlmediakit_secret' => Config::get('app.ak_config.zlmediakit_secret'),
            'debug' => Config::get('app.ak_config.debug'),
        ];

        $dbConfig = $this->get('ak_config', []);
        $dbConfig === null && $dbConfig = [];
        $currentConfig = array_merge($config, $dbConfig);
        $currentConfig['debug'] = intval($currentConfig['debug']) . '';

        return $currentConfig;
    }

    public function set($name, $value)
    {
        $this->getSettingDao()->deleteByName($name);
        $setting = [
            'name' => $name,
            'value' => serialize($value),
        ];
        return $this->getSettingDao()->create($setting);
    }

    public function get($name, $default = null, $namespace = 'default')
    {
        $setting = $this->getSettingDao()->findByNameAndNamespace($name, $namespace);
        if (empty($setting)) {
            return null;
        }

        return is_string($setting['value']) ? unserialize($setting['value']) : $setting['value'];
    }

    public function delete($name)
    {
        return $this->getSettingDao()->deleteByName($name);
    }

    public function setByNamespace($namespace, $name, $value)
    {
        $this->getSettingDao()->deleteByNamespaceAndName($namespace, $name);
        $setting = [
            'namespace' => $namespace,
            'name' => $name,
            'value' => serialize($value),
        ];
        return $this->getSettingDao()->create($setting);
    }


    public function deleteByNamespaceAndName($namespace, $name)
    {
        return $this->getSettingDao()->deleteByNamespaceAndName($namespace, $name);
    }


    /**
     * @return SettingDao
     */
    protected function getSettingDao()
    {
        return $this->createDao('Setting:SettingDao');

    }

}
