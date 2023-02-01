<?php

namespace Biz\Setting\Service;

interface SettingService
{
    public function set($name, $value);

    public function setByNamespace($namespace, $name, $value);

    public function get($name, $default = null, $namespace = 'default');

    public function delete($name);

    public function deleteByNamespaceAndName($namespace, $name);

    public function getAkServerConfig();

}
