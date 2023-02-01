<?php


namespace Biz\AkStreamSdk\Resources;


use Biz\AkStreamSdk\HttpClient\Client;
use Biz\AkStreamSdk\HttpClient\Response;

abstract class AbstractResource
{
    /**
     * @var Client
     */
    protected $client;

    protected $errorCodes = [
        'None' => [0, '成功'], //成功
        'Sys_GetMacAddressExcept' => [-1000, '获取Mac地址异常'], //获取Mac地址异常
        'Sys_GetIpAddressExcept' => [-1001, '获取IP地址异常'], //获取IP地址异常
        'Sys_JsonWriteExcept' => [-1002, 'Json写入异常'], //Json写入异常
        'Sys_JsonReadExcept' => [-1003, 'Json读取异常'], //Json读取异常
        'Sys_ConfigDirNotExists' => [-1004, '配置文件目录不存在'], //配置文件目录不存在
        'Sys_ConfigFileNotExists' => [-1005, '配置文件不存在'], //配置文件不存在
        'Sys_ParamsNotEnough' => [-1006, '参数不足'], //参数不足
        'Sys_ParamsIsNotRight' => [-1007, '参数不正确'], //参数不正确
        'Sys_WebApi_Except' => [-1008, 'WebApi异常'], //WebApi异常
        'Sys_ConfigNotReady' => [-1009, '配置文件没有就绪'], //配置文件没有就绪
        'Sys_DataBaseNotReady' => [-1010, '数据库没有就绪'], //数据库没有就绪
        'Sys_NetworkPortExcept' => [-1011, '端口不可用'], //端口不可用
        'Sys_DiskInfoExcept' => [-1012, '磁盘不可用'], //磁盘不可用
        'Sys_UrlExcept' => [-1013, '参数中URL异常'], //参数中URL异常
        'Sys_ReadIniFileExcept' => [-1014, '读取ini文件异常'], //读取ini文件异常
        'Sys_WriteIniFileExcept' => [-1015, '写入ini文件异常'], //写入ini文件异常
        'Sys_SocketPortForRtpExcept' => [-1016, '查找可用rtp端口时异常，可能已无可用端口'], //查找可用rtp端口时异常，可能已无可用端口
        'Sys_SpecifiedFileNotExists' => [-1017, '指定文件不存在'], //指定文件不存在
        'Sys_InvalidAccessKey' => [-1018, '访问密钥失效'], //访问密钥失效
        'Sys_AKStreamKeeperNotRunning' => [-1019, '流媒体服务器治理程序没有运行'], //AKStreamKeeper流媒体服务器治理程序没有运行
        'Sys_DataBaseLimited' => [-1020, '数据库操作受限，请检查相关参数'], //数据库操作受限，请检查相关参数，如分页查询时每页不能超过10000行
        'Sys_DB_VideoChannelNotExists' => [-1021, '数据库中不存在指定音视频通道,此设备可能已激活'], //数据库中不存在指定音视频通道,此设备可能已激活
        'Sys_DataBaseExcept' => [-1022, '数据库执行异常'], //数据库执行异常
        'Sys_DB_VideoChannelAlRedayExists' => [-1023, '数据库中已经存在指定音视频通道'], //数据库中已经存在指定音视频通道
        'Sys_DB_RecordNotExists' => [-1024, '数据库中指定记录不存在'], //数据库中指定记录不存在
        'Sys_VideoChannelNotActived' => [-1025, '音视频通实例没有激活'], //音视频通实例没有激活
        'Sys_HttpClientTimeout' => [-1026, 'http客户端请求超时'], //http客户端请求超时
        'Sys_DB_RecordPlanNotExists' => [-1027, '录制计划不存在'], //录制计划不存在
        'Sys_RecordPlanTimeLimitExcept' => [-1028, '录制计划时间间隔异常'], //录制计划时间间隔异常
        'Sys_DB_RecordPlanAlreadyExists' => [-1029, '数据库中指定录制计划已经存在'], //数据库中指定录制计划已经存在
        'Sys_DvrCutMergeTimeLimit' => [-1030, '裁剪时间限制，超过120分钟任务不允许执行'], //裁剪时间限制，超过120分钟任务不允许执行
        'Sys_DvrCutMergeFileNotFound' => [-1031, '时间周期内没有找到相关视频文件'], //时间周期内没有找到相关视频文件
        'Sys_DvrCutProcessQueueLimit' => [-1032, '理队列已满，请稍后再试'], //处理队列已满，请稍后再试
        'Sip_StartExcept' => [-2000, '启动Sip服务异常'], //启动Sip服务异常
        'Sip_StopExcept' => [-2001, '停止Sip服务异常'], //停止Sip服务异常
        'Sip_Except_DisposeSipDevice' => [-2002, 'Sip网关内部异常(销毁Sip设备时)'], //Sip网关内部异常(销毁Sip设备时)
        'Sip_Except_RegisterSipDevice' => [-2003, 'Sip网关内部异常(注册Sip设备时)'], //Sip网关内部异常(注册Sip设备时)
        'Sip_ChannelNotExists' => [-2004, 'Sip音视频通道不存在'], //Sip音视频通道不存在
        'Sip_DeviceNotExists' => [-2005, 'Sip设备不存在'], //Sip设备不存在
        'Sip_OperationNotAllowed' => [-2006, '该设备类型下不允许这个操作'], //该设备类型下不允许这个操作
        'Sip_DeInviteExcept' => [-2007, '结束推流时异常'], //结束推流时异常
        'Sip_InviteExcept' => [-2008, '推流时异常'], //推流时异常
        'Sip_SendMessageExcept' => [-2009, '发送sip消息时异常'], //发送sip消息时异常
        'Sip_AlredayPushStream' => [-2010, 'sip通道已经在推流状态'], //sip通道已经在推流状态
        'Sip_NotOnPushStream' => [-2011, 'Sip通道没有在推流状态'], //Sip通道没有在推流状态
        'Sip_Channel_StatusExcept' => [-2012, 'Sip通道设备状态异常'], //Sip通道设备状态异常
        'Sip_VideoLiveExcept' => [-2013, 'Sip通道推流请求异常'], //Sip通道推流请求异常
        'MediaServer_WebApiExcept' => [-3000, '访问流媒体服务器WebApi时异常'], //访问流媒体服务器WebApi时异常
        'MediaServer_WebApiDataExcept' => [-3001, '访问流媒体服务器WebApi接口返回数据异常'], //访问流媒体服务器WebApi接口返回数据异常
        'MediaServer_TimeExcept' => [-3002, '服务器时间异常，建议同步'], //服务器时间异常，建议同步
        'MediaServer_BinNotFound' => [-3003, '流媒体服务器可执行文件不存在'], //流媒体服务器可执行文件不存在
        'MediaServer_ConfigNotFound' => [-3004, '流媒体服务器配置文件不存在，建议手工运行一次流媒体服务器使其自动生成配置文件模板'], //流媒体服务器配置文件不存在，建议手工运行一次流媒体服务器使其自动生成配置文件模板
        'MediaServer_InstanceIsNull' => [-3005, '流媒体服务实例为空，请先创建流媒体服务实例'], //流媒体服务实例为空，请先创建流媒体服务实例
        'MediaServer_StartUpExcept' => [-3006, '启动流媒体服务器失败'], //启动流媒体服务器失败
        'MediaServer_ShutdownExcept' => [-3007, '停止流媒体服务器失败'], //停止流媒体服务器失败
        'MediaServer_RestartExcept' => [-3008, '重启流媒体服务器失败'], //重启流媒体服务器失败
        'MediaServer_ReloadExcept' => [-3009, '流媒体服务器配置热加载失败'], //流媒体服务器配置热加载失败
        'MediaServer_NotRunning' => [-3010, '流媒体服务器没有运行'], //流媒体服务器没有运行
        'MediaServer_OpenRtpPortExcept' => [-3011, '申请rtp端口失败，申请端口可能已经存在'], //申请rtp端口失败，申请端口可能已经存在
        'MediaServer_WaitWebHookTimeOut' => [-3012, '等待流媒体服务器回调时超时'], //等待流媒体服务器回调时超时
        'MediaServer_StreamTypeExcept' => [-3013, '流类型不正确'], //流类型不正确
        'MediaServer_GetStreamTypeExcept' => [-3014, '指定拉流方法不正确'], //指定拉流方法不正确
        'MediaServer_VideoSrcExcept' => [-3015, '源流地址异常'], //源流地址异常
        'Other' => [-6000, '其他异常'], //其他异常
        'UnknownError' => [-1, '未知错误']
    ];

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return $this
     */
    public function configure()
    {
        return $this;
    }

    /**
     * @param $path
     * @param array $parameters
     * @param array $requestHeaders
     * @return Response
     */
    protected function get($path, array $parameters = [], $requestHeaders = [])
    {
        return $this->client->get($path, $parameters, $requestHeaders);
    }

    /**
     * @param $path
     * @param array $parameters
     * @param array $queryParams
     * @param array $requestHeaders
     * @return Response
     */
    protected function post($path, array $parameters = [], array $queryParams = [], $requestHeaders = [])
    {
        return $this->client->post($path, $parameters, $queryParams, $requestHeaders);
    }

    /**
     * @param $params
     * @param array $necessary
     */
    protected function checkParamIsSet($params, array $necessary)
    {
        for ($i = 0; $i < count($necessary); ++$i) {
            if (!array_key_exists($necessary[$i], $params)) {
                throw new \InvalidArgumentException('参数' . $necessary[$i] . '必须传递');
            }
        }
    }

    /**
     * @param $code
     * @return mixed|null
     */
    protected function getErrorNumberByCode($code)
    {
        return isset($this->errorCodes[$code]) ? $this->errorCodes[$code][0] : null;
    }

    /**
     *
     * 获取自定义错误
     * @param $code
     * @return mixed|null
     */
    protected function getErrorSelfMessageByCode($code)
    {
        return isset($this->errorCodes[$code]) ? $this->errorCodes[$code][1] : null;
    }

    /**
     * @param $uri
     * @param array $params
     * @return array|null[]|string|null
     */
    protected function clientGet($uri, array $params = [])
    {
        return $this->responseFormat($this->client->get($uri, $params));
    }

    /**
     * @param $uri
     * @param array $data
     * @param array $params
     * @return array|null[]|string|null
     */
    protected function clientPost($uri, $data = [], $params = [])
    {
        $response = $this->client->post($uri, $data, $params);

        return $this->responseFormat($response);
    }

    /**
     * @param Response $response
     * @return array|string|null
     */
    protected function responseFormat(?Response $response)
    {
        if (!$response instanceof Response) {
            return [$this->getErrorNumberByCode('Other'), null, $this->getErrorSelfMessageByCode('Other')];
        }

        $headers = $response->getHeaders();
        if (isset($headers['Content-Type']) &&
            (
                false !== strrpos($headers['Content-Type'], "application/json") ||
                false !== strrpos($headers['Content-Type'], "text/json"))
        ) {
            $data = json_decode($response->getBody(), true);
            if (isset($data['Code'])) {
                $msg = empty($data['Message']) ? $this->getErrorSelfMessageByCode($data['Code']) : $data['Message'];
                return [$this->getErrorNumberByCode($data['Code']), null, $msg];
            }

            if (isset($data['code']) && $data['code'] == -1) {
                return [$this->getErrorNumberByCode('None'), null, $this->getErrorSelfMessageByCode('None')];
            }

            return [$this->getErrorNumberByCode('None'), $data, $this->getErrorSelfMessageByCode('None')];
        }

        if (200 !== $response->getHttpResponseCode()) {
            return [$this->getErrorNumberByCode('Other'), null, $this->getErrorSelfMessageByCode('Other')];
        }

        return [$this->getErrorNumberByCode('None'), $response->getBody(), $this->getErrorSelfMessageByCode('None')];

    }
}