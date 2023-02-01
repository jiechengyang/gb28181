<?php


namespace Biz\Ys7Sdk;

use Codeages\Biz\Framework\Context\Biz;
use \Exception;

class OpenYs7
{

    /**
     *
     */
    const ACCESS_TOKEN_KEY = 'open:ys7:access_token';

    /**
     * @var mixed
     */
    private $appKey;

    /**
     * @var mixed
     */
    private $appSecret;

    /**
     * token过期时间，单位：s
     * @var int|mixed
     */
    private $tokenTtl = 604200;// 7天-10分钟

    /**
     * @var mixed|string
     */
    private $module = 'lapp';

    /**
     * @var string
     */
    private $apiUri = 'https://open.ys7.com/api';

    /**
     * @var bool
     */
    private $hashKey = false;

    /**
     *
     * 开发平台api错误
     * @var array
     */
    public $errorMap = [
        10001 => '参数为空或格式不正确',//appKey不能为空
        10002 => '重新获取accessToken',
        10005 => 'appKey被冻结',
        10030 => 'appKey和appSecret不匹配',
        20002 => '设备不存在',
        20006 => '检查设备网络状况，稍后再试',
        20007 => '检查设备是否在线',
        20008 => '操作过于频繁，稍后再试',
        20014 => 'deviceSerial不合法',
        20018 => '该用户不拥有该设备, 检查设备是否属于当前账户',
        20032 => '该用户下通道不存在',
        49999 => '数据异常',
        60000 => '设备不支持云台控制',
        60001 => '用户无云台控制权限',
        60002 => '设备云台旋转达到上限位',
        60003 => '设备云台旋转达到下限位',
        60004 => '设备云台旋转达到左限位',
        60005 => '设备云台旋转达到右限位',
        60006 => '云台当前操作失败，请稍候再试',
        60009 => '正在调用预置点',
        60020 => '不支持该命令,确认设备是否支持预览操作',
        60061 => '账户流量已超出或未购买，限制开通',
        60062 => '该通道直播已开通',
    ];

    /**
     *
     * 云台操作指令
     * @var array
     */
    public $directionMap = [
        0 => '上',
        1 => '下',
        2 => '左',
        3 => '右',
        4 => '左上',
        5 => '左下',
        6 => '右上',
        7 => '右下',
        8 => '放大',
        9 => '缩小',
        10 => '近焦距',
        11 => '远焦距',
    ];

    /**
     * @var null
     */
    public $hockAfterSend = null;

    private $biz;


    public function __construct(Biz $biz)
    {
        $this->biz = $biz;
    }

    public function init(array $params)
    {
        $this->appKey = $params['appKey'];
        if (empty($this->appKey)) {
            throw new Exception("muse be hav appKey");
        }

        $this->appSecret = $params['appSecret'];
        if (empty($this->appSecret)) {
            throw new Exception("muse be hav appSecret");
        }
        !empty($params['tokenTtl']) && $this->tokenTtl = $params['tokenTtl'];
        !empty($params['module']) && $this->module = $params['module'];
        $this->generateUrl();
    }

    /**
     *
     * 更替module，默认的module为lapp
     * @param $module
     * @return $this
     */
    public function module($module)
    {
        $this->module = $module;
        $this->generateUrl();
        return $this;
    }

    /**
     * 生成接口前缀
     * @return string
     */
    private function generateUrl()
    {
        $this->apiUri .= strpos($this->module, '/') === 0 ? $this->module : '/' . $this->module;
        return rtrim($this->apiUri, '/');
    }

    /**
     * @param array $data
     * @return array
     */
    private function packageData($data = [])
    {
        $data = array_merge([
            'accessToken' => $this->getAccessToken()
        ], $data);
        return $data;
    }

    /**
     * @param $res
     * @return array|mixed
     * @throws Exception
     */
    private function parseResponse($res)
    {
        $data = $this->decodeResult($res);
        $this->afterSend($data);
        if (!$this->isOk($data['code'])) {
            throw new Exception($this->error($data['code'], $data['msg']));
        }

        return $data['data'];
    }

    //region 接口请求

    /**
     * @param $url
     * @param array $data
     * @return mixed
     */
    public function send($url, $data)
    {
        $this->beforeSend($url, $data);
        $res = $this->curl()->post($url, $data);
        $this->afterSend($res);
        return $res;
    }

    /**
     *
     * api before send
     * @param $url
     * @param $data
     */
    public function beforeSend($url, &$data)
    {
        if (isset($data['pageStart'])) {
            $data['pageStart'] += 0;
            $data['pageStart'] < 0 && $pageStart = 0;
        }

        if (isset($data['pageSize'])) {
            $data['pageSize'] += 0;
            $data['pageSize'] < 0 && $data['pageSize'] = 10;
            if ($data['pageSize'] > 50)
                throw new Exception("最大分页数:50");
        }
    }

    /**
     *
     * api after send
     * @param $res
     */
    public function afterSend($res)
    {
        if ($this->hockAfterSend instanceof \Closure) {
            $res = json_decode($res, true);
            if (!$this->isOk($res['code'])) {
                call_user_func_array($this->hockAfterSend, [$this, $res]);
            }
        }
    }

    /**
     *
     * 请求获取accessToken
     * @return string|null
     * @throws Exception
     */
    public function getToken()
    {
        $url = $this->apiUri . '/token/get';
        $data = [
            'appKey' => $this->appKey,
            'appSecret' => $this->appSecret
        ];

        $res = $this->send($url, $data);
        $data = $this->parseResponse($res);
        $accessToken = $data['accessToken'];
        $this->setAccessToken($accessToken);
        return $accessToken;
    }

    /**
     *
     * 获取设备列表
     * @param int $pageStart
     * @param int $pageSize
     */
    public function getDeviceList($pageStart = 0, $pageSize = 10)
    {
        $url = $this->apiUri . '/device/list';

        $res = $this->send($url, $this->packageData([
            'pageStart' => $pageStart,
            'pageSize' => $pageSize,
        ]));

        return $this->parseResponse($res);
    }


    /**
     *
     * 获取单个设备信息
     * @param $deviceSerial
     */
    public function getDeviceInfo($deviceSerial)
    {
        $url = $this->apiUri . '/device/info';
        $res = $this->send($url, $this->packageData([
            'deviceSerial' => $deviceSerial,
        ]));

        return $this->parseResponse($res);
    }


    /**
     *
     * 摄像头列表
     * @param int $pageStart
     * @param int $pageSize
     * @return mixed
     * @throws Exception
     */
    public function getCameraList($pageStart = 0, $pageSize = 32)
    {
        $url = $this->apiUri . '/camera/list';
        $res = $this->send($url, $this->packageData([
            'pageStart' => $pageStart,
            'pageSize' => $pageSize,
        ]));
        return $this->parseResponse($res);
    }


    /**
     *
     * 直播列表
     * @param int $pageStart
     * @param int $pageSize
     * @return mixed
     */
    public function getLiveList($pageStart = 0, $pageSize = 32)
    {
        $url = $this->apiUri . '/live/video/list';
        $res = $this->send($url, $this->packageData([
            'pageStart' => $pageStart,
            'pageSize' => $pageSize,
        ]));
        return $this->parseResponse($res);
    }

    public function getCameraLiveUrl($deviceSerial, $channelNo = 1, $otherParams = [])
    {
        $url = $this->apiUri . '/v2/live/address/get';
        $result = $this->send($url, $this->packageData([
            'deviceSerial' => $deviceSerial,
            'channelNo' => $channelNo,
            'protocol' => isset($otherParams['protocol']) ? $otherParams['protocol'] : 2,
            'expireTime' => isset($otherParams['expireTime']) ? $otherParams['expireTime'] : 3600 * 24 * 7,
            'quality' => isset($otherParams['quality']) ? $otherParams['quality'] : 2,
        ]));

        return $this->parseResponse($result);
    }

    /**
     *
     * 开通直播功能
     * @param $source 直播源，[设备序列号]:[通道号],[设备序列号]:[通道号]的形式，例如427734222:1,423344555:3，均采用英文符号，限制50个
     * @return mixed
     */
    public function openCameraLive($source)
    {
        $url = $this->apiUri . '/live/video/open';
        $res = $this->send($url, $this->packageData([
            'source' => $source
        ]));
        return $this->parseResponse($res);
    }

    /**
     *
     * 开始云台控制(对设备进行开始云台控制，开始云台控制之后必须先调用停止云台控制接口才能进行其他操作，包括其他方向的云台转动)
     * @param $deviceSerial null 设备序列号,存在英文字母的设备序列号，字母需为大写
     * @param int $channelNo null 通道号
     * @param int $direction null 操作命令
     * @param int $speed null 云台速度
     * @return array|mixed
     * @throws Exception
     */
    private function devicePtzStart($deviceSerial, $channelNo = 1, $direction = 0, $speed = 1)
    {
        if (!key_exists($direction, $this->directionMap)) {
            throw new Exception("不支持该指令");
        }

        $url = $this->apiUri . '/device/ptz/start';
        $speed < 1 && $speed = 1;
        $params = [
            'deviceSerial' => $deviceSerial,
            'channelNo' => $channelNo,
            'direction' => $direction,
            'speed' => $speed
        ];
        $res = $this->send($url, $this->packageData($params));
        return $this->parseResponse($res);
    }

    /**
     *
     * 设备停止云台控制
     * @param $deviceSerial null 设备序列号,存在英文字母的设备序列号，字母需为大写
     * @param int $channelNo null 通道号
     * @param int $direction null 操作命令
     * @return array|mixed
     * @throws Exception
     */
    public function devicePtzStop($deviceSerial, $channelNo = 1, $direction = 0)
    {
        if (!key_exists($direction, $this->directionMap)) {
            throw new Exception("不支持该指令");
        }

        $url = $this->apiUri . '/device/ptz/stop';
        $res = $this->send($url, $this->packageData([
            'deviceSerial' => $deviceSerial,
            'channelNo' => $channelNo,
            'direction' => $direction,
        ]));

        return $this->parseResponse($res);

    }


    /**
     *
     * 设备云台控制，开始云台控制之后必须先调用停止云台控制接口才能进行其他操作，包括其他方向的云台转动
     * @param $deviceSerial
     * @param int $channelNo
     * @param int $direction
     * @param int $speed
     * @return bool
     */
    public function deviceControl($deviceSerial, $channelNo = 1, $direction = 0, $speed = 1)
    {
        $result = $this->devicePtzStart($deviceSerial, $channelNo, $direction, $speed);
        if ($this->isOk($result['code'])) {
            $res = $this->devicePtzStop($deviceSerial, $channelNo, $direction);
            return $this->isOk($res['code']);
        }

        return false;
    }

    /**
     *
     * 查询账号下流量消耗汇总
     * @return array|mixed
     */
    public function trafficUserTotal()
    {
        $url = $this->apiUri . '/traffic/user/total';
        $res = $this->send($url, $this->packageData());

        return $this->parseResponse($res);
    }


    /**
     * 获取账号下的所有告警消息列表
     *
     * @return array|mixed
     * @throws Exception
     */
    public function alarmList()
    {
        $url = $this->apiUri . '/alarm/list';
        $res = $this->send($url, $this->packageData());

        return $this->parseResponse($res);
    }

    //endregion

    /**
     * 请求是否成功
     * @param $code
     * @return bool
     */
    public function isOk($code)
    {
        return (string)$code === '200';
    }

    /**
     * 输出错误信息
     */
    public function error($code = -1, $msg = '')
    {
        empty($msg) && $msg = '未知错误';
        return isset($this->errorMap[$code]) ? $this->errorMap[$code] : $msg;
    }

    /**
     * curl 操作类
     * @return CurlRequest
     */
    protected function curl()
    {
        $curl = new CurlRequest();
        $headers = ['Content-Type: application/x-www-form-urlencoded'];

        return $curl->setOption(CURLOPT_HTTPHEADER, $headers)
            ->setOption(CURLOPT_CONNECTTIMEOUT, 60)
            ->setOption(CURLOPT_TIMEOUT, 120);
    }

    public function refererToken()
    {
//        if ($this->cache->has(self::ACCESS_TOKEN_KEY)) {
//            $this->cache->delete(self::ACCESS_TOKEN_KEY);
//        }
//
//        return $this->getToken();
    }

    /**
     * 获取 缓存的accessToken
     * 过期则重新从接口获取
     * @return mixed|null|string
     */
    public function getAccessToken()
    {
//        $token = $this->cache->get(self::ACCESS_TOKEN_KEY);
//        if (empty($token)) {
//            $token = $this->getToken();
//        }
//
//        return $token;
    }

    /**
     * 保存accessToken至redis
     * @param $token
     * @throws Exception
     */
    protected function setAccessToken($token)
    {
//        if (!$this->cache->set(self::ACCESS_TOKEN_KEY, $token, $this->tokenTtl)) {
//            throw new Exception("redis set token failed!!!");
//        }
    }

    /**
     * 解码接口数据
     * @param $result
     * @return mixed
     */
    protected function decodeResult($result)
    {
        return json_decode($result, true);
    }
}