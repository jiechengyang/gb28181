<?php


namespace app\admin\controller;


use app\admin\BaseController;
use Biz\Setting\Exception\SettingException;
use Biz\Setting\Service\SettingService;
use support\Request;

class Setting extends BaseController
{
    public function vueRouters(Request $request)
    {
        return $this->createSuccessJsonResponse(config('vue'));
    }

    public function getValue(Request $request)
    {
        $name = $request->get('name');
        if (empty($name)) {
            return $this->createFailJsonResponse('配置项不存在');
        }

        return $this->createSuccessJsonResponse($this->getSettingService()->get($name));
    }

    public function editValue(Request $request)
    {
        $name = $request->get('name');
        if (empty($name)) {
            return $this->createFailJsonResponse('配置项不存在');
        }

        $config = $request->post();
        $value = $this->getSettingService()->set($name, $config);

        return $this->createSuccessJsonResponse($value);
    }

    public function getIpBlackList(Request $request)
    {
        return $this->createSuccessJsonResponse([
            'blackListIps' => $this->getSettingService()->get('blacklist_ip'),
            'whiteListIps' => $this->getSettingService()->get('whitelist_ip'),
        ]);
    }

    public function editIpBlackList(Request $request)
    {
        $blackListIps = $request->post('blackListIps');
        $whiteListIps = $request->post('whiteListIps');
        $purifiedBlackIps = trim(preg_replace('/s+/', ' ', $blackListIps));
        $purifiedWhiteIps = trim(preg_replace('/s+/', ' ', $whiteListIps));
        if (empty($purifiedBlackIps)) {
            $this->getSettingService()->delete('blacklist_ip');
            $purifiedBlackIps = [];
        } else {
            $purifiedBlackIps = array_filter(explode(' ', $purifiedBlackIps));
            $this->getSettingService()->set('blacklist_ip', $purifiedBlackIps);
        }

        if (empty($purifiedWhiteIps)) {
            $this->getSettingService()->delete('whitelist_ip');
            $purifiedWhiteIps = [];
        } else {
            $purifiedWhiteIps = array_filter(explode(' ', $purifiedWhiteIps));
            $this->getSettingService()->set('whitelist_ip', $purifiedWhiteIps);
        }

        $this->getLogService()->info('system', 'update_settings', '更新IP黑名单/白名单', [
            'blacklist_ip' => $purifiedBlackIps,
            'whitelist_ip' => $purifiedWhiteIps,
            'currentIp' => $request->getRealIp()
        ]);

        $this->createSuccessJsonResponse();
    }

    public function getAkServer(Request $request)
    {
        return $this->createSuccessJsonResponse($this->getSettingService()->getAkServerConfig());
    }

    public function editAKServer(Request $request)
    {
        $config = $request->post();
        if (empty($config['api_url'])) {
            throw  SettingException::AK_CONFIG_API_EMPTY();
        }

        if (empty($config['access_key'])) {
            throw  SettingException::AK_CONFIG_AK_EMPTY();
        }

        empty($config['debug']) && $config['debug'] = false;
        $config['debug'] = boolval($config['debug']);
        $this->getSettingService()->set('ak_config', $config);

        return $this->createSuccessJsonResponse();
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('Setting:SettingService');
    }
}