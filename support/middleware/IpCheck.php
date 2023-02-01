<?php


namespace support\middleware;


use Biz\Setting\Service\SettingService;
use Codeages\Biz\Framework\Context\Biz;
use support\bootstrap\BizInit;
use support\bootstrap\Container;
use Topxia\Service\Common\ServiceKernel;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class IpCheck implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        $blacklistIps = $this->getSettingService()->get('blacklist_ip');
        $whitelistIps = $this->getSettingService()->get('whitelist_ip');
        $clientIp = $request->getRealIp();
        if (!empty($blacklistIps)) {
            if ($this->matchIpConfigList($clientIp, $blacklistIps)) {
                return json(['code' => 403001, 'message' => "ip被封，无权访问"], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE, 403);
            }
        }

        if (!empty($whitelistIps)) {
            if (!$this->matchIpConfigList($clientIp, $whitelistIps)) {
                return json(['code' => 403001, 'message' => 'ip被封，无权访问'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE, 403);
            }
        }

        return $next($request);
    }

    private function matchIpConfigList($clientIp, $ipConfigList)
    {
        foreach ($ipConfigList as $ipConfigEntry) {
            if ($this->matchIp($clientIp, $ipConfigEntry)) {
                return true;
            }
        }

        return false;
    }

    private function matchIp($clientIp, $ipConfigEntry)
    {
        $ipConfigEntry = trim($ipConfigEntry);

        if (strlen($ipConfigEntry) > 0) {
            $regex = str_replace('.', "\.", $ipConfigEntry);
            $regex = str_replace('*', "\d{1,3}", $regex);
            $regex = '/^' . $regex . '/';

            return preg_match($regex, $clientIp);
        } else {
            return false;
        }
    }

    /**
     * @return Biz
     */
    protected function getBiz()
    {
        return BizInit::init();
//        return Container::get(Biz::class);
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->getBiz()->service('Setting:SettingService');
    }
}