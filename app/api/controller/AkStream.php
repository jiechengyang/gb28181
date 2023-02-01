<?php

namespace app\api\controller;

use Biz\Constants;
use Biz\DeviceActiveLog\Service\DeviceActiveLogService;
use Biz\DeviceRegisterLog\Service\DeviceRegisterLogService;
use Biz\GB28281\DeviceStatus;
use Biz\PlayRecord\Service\PlayRecordService;
use Biz\Record\Service\RecordService;
use Biz\SystemLog\Service\SystemLogService;
use Biz\VideoChannels\Service\VideoChannelsService;
use Biz\VideoRecorder\Service\VideoRecorderService;
use support\bootstrap\Log;
use support\Request;
use support\Response;
use support\utils\ArrayToolkit;
use Webman\Config;

/**
 *
 * @desc AkStream WebHock  .netCore平台业务会掉接口
 * Class AkStream
 * @package app\api\controller
 */
class AkStream extends \app\AbstractController
{
    public function beforeAction(Request $request)
    {
        parent::beforeAction($request);

        $userAgent = $request->header('user-agent', '');
        $authSign = $request->header('x-auth-sign', '');
        $mySign = $this->makeSign($userAgent);
        if ($mySign !== $authSign) {
            return json(['code' => self::BIS_FAILED_CODE, 'data' => null, 'message' => '签名错误、非法请求Hock']);
        }

        $uri = $request->uri();
        $empty = (strpos($uri, '/api/akStream/onDeviceReadyReceived') !== false
            || strpos($uri, '/api/akStream/onDeviceStatusReceived') !== false);
        $this->getLogger()->info('BLiveHock request:', [
//            'real_ip' => $request->getRealIp(),
//            'remote_ip' => $request->getRealIp(),
//            'headers' => $request->header(),
            'uri' => $uri,
            'post_data' => $empty ? '' : $request->post(),
//            'get_data' => $request->get(),
        ]);
    }

    public function afterAction(Request $request, $response)
    {
        /** @var $response Response */
        parent::afterAction($request, $response);
//        $this->getLogger()->info('BLiveHock response', [
//            'statusCode' => $response->getStatusCode(),
//            'body' => $response->rawBody()
//        ]);
    }

    public function onRecordMp4(Request $request)
    {
        $data = $this->getPostData($request);
        if (!empty($data['DownloadUrl'])) {
            list($code, $message) = $this->getRecordService()->addRecordFile($data);
            if ($code == -1) {
                $this->getSystemLogService()->info('record_callback', 'file_error', $message);
            }
        }
    }

    public function onAuthTaskOther(Request $request)
    {
        $data = $this->getPostData($request);
        // 部分监控掉线
        if (!empty($data['delItems'])) {

        }

        // 全部监控掉线
        if (isset($data['offlineAll'])) {

        }

        return json(['code' => self::BIS_SUCCESS_CODE, 'message' => 'AkStream onAuthTaskOther', 'data' => null]);
    }


    /**
     * 有播放者的时候
     * @param Request $request
     */
    public function onStreamPlay(Request $request)
    {
        $data = $this->getPostData($request);
        if (empty($data['MainId'])) {
            return json(['code' => 4034001, 'message' => 'Not Access Link', 'data' => null], 403);
        }

        $videoChannel = $this->getVideoChannelsService()->getVideoChannelByMainId($data['MainId']);
        if (empty($videoChannel)) {
            return json(['code' => 4034001, 'message' => 'Not Access Link', 'data' => null], 403);
        }

        $postData = [
            'vc_id' => $videoChannel['id'],
            'code' => $data['MainId'],
            'media_server_id' => $data['MediaServerId'],
            'client_ip' => $data['IpAddress'],
            'player_id' => $data['PlayerId'],
            'server_port' => $data['Port'],
            'params' => $data['Params'],
            'startTime' => strtotime($data['StartTime'])
        ];
//        $this->getSystemLogService()->info('ak-hock', 'onStreamPlay', '有播放者的时候', $postData);
        $this->getPlayRecordService()->createPlayRecord($postData);


        return json(['code' => self::BIS_SUCCESS_CODE, 'message' => 'AkStream onStreamPlay', 'data' => null]);
    }

    /**
     *
     * 注册 写入 smp_device_register_log
     * @param Request $request
     * @return Response
     */
    public function onRegisterReceived(Request $request)
    {
//        {
//            "IpAddress": "192.168.0.242",
//		"DeviceId": "34020000001320000001",
//		"Port": 5060,
//		"SipChannels": [],
//		"DeviceInfo": {
//            "CmdType": 0,
//			"SN": 0,
//			"DeviceID": "34020000001320000001",
//			"Channel": 0
//		},
//		"RegisterTime": "2021-10-24 22:22:47",
//		"Username": "",
//		"Password": "",
//		"KeepAliveTime": "2021-10-24 22:22:47",
//		"KeepAliveLostTime": 0,
//		"IsReday": false
//	}
        $data = $this->getPostData($request);
        if (!empty($data['DeviceId'])) {
            $fields = [
                'deviceId' => $data['DeviceId'],
                'ipAddress' => $data['IpAddress'],
                'isReady' => (int)$data['IsReday'],
                'registerTime' => strtotime($data['RegisterTime']),
                'data' => serialize($data),
                'type' => 'registered'
            ];

            $this->getDeviceRegisterLogService()->createRegisterLog($fields);
        }

        if (!empty($data['DeviceInfo']) && $data['IsReday'] == true) {
            $this->getVideoRecorderService()->syncVideoRecorder($data);
        }

        if (!empty($data['SipChannels'])) {
            $this->batchUpdateAddress($data['SipChannels'], $this->getVideoRecorderService()->getIsVideoRecorderDevice($data['DeviceId']));
        }

        return json(['code' => self::BIS_SUCCESS_CODE, 'message' => 'AkStream OnRegisterReceived', 'data' => null]);
    }

    /**
     *
     * 注销 写入 smp_device_register_log
     * @param Request $request
     * @return Response
     * @todo 修改设备状态
     */
    public function onUnRegisterReceived(Request $request)
    {
        $data = $this->getPostData($request);
        if (!empty($data['DeviceId'])) {
            $fields = [
                'deviceId' => $data['DeviceId'],
                'ipAddress' => $data['IpAddress'],
                'isReady' => (int)$data['IsReday'],
                'registerTime' => strtotime($data['RegisterTime']),
                'data' => serialize($data),
                'type' => 'unRegistered'
            ];
            $this->getDeviceRegisterLogService()->createRegisterLog($fields);
            if (!empty($data['DeviceInfo']) && $data['IsReday'] == true) {
                $data['DeviceStatus']['Online'] = "OFFLINE";
                $this->getVideoRecorderService()->syncVideoRecorder($data, 'unRegisterReceived');
            }
        }

        return json(['code' => self::BIS_SUCCESS_CODE, 'message' => 'AkStream onUnRegisterReceived', 'data' => null]);
    }

    public function onKeepaliveReceived(Request $request)
    {
        $data = $this->getPostData($request);
        if (empty($data)) {
            return json(['code' => self::BIS_FAILED_CODE, 'message' => '未收到数据', 'data' => null]);
        }

        $status = $data['lostTimes'] > 3 ? Constants::DEVICE_STATUS_OFFLINE : Constants::DEVICE_STATUS_ONLINE;
        if ($this->getVideoRecorderService()->getIsVideoRecorderDevice($data['deviceId'])) {
            $this->getVideoRecorderService()->changeStatusWithKeepalive($data['deviceId'], $status);

        } else {
            $this->getVideoChannelsService()->updateStatusWithKeepalive($data['deviceId'], $status);
        }

//        $data['keepAliveTime'] = strtotime($data['keepAliveTime']);
//        $this->getDeviceActiveLogService()->createActiveLog($data);

        return json(['code' => self::BIS_SUCCESS_CODE, 'message' => 'AkStream onKeepaliveReceived', 'data' => null]);
    }

    /**
     * nvr 注册
     * @param Request $request
     * @return Response
     * @todo 设备就绪
     */
    public function onDeviceReadyReceived(Request $request)
    {
        $data = $this->getPostData($request);
        if (!empty($data['DeviceInfo'])) {
            $this->getVideoRecorderService()->syncVideoRecorder($data);
        }

        return json(['code' => self::BIS_SUCCESS_CODE, 'message' => 'AkStream onDeviceReadyReceived', 'data' => null]);
    }

    /**
     *
     * 写入状态
     * @param Request $request
     * @return Response
     */
    public function onDeviceStatusReceived(Request $request)
    {
        $postData = $this->getPostData($request);
//        $this->getSystemLogService()->info('ak-hock', 'onDeviceStatusReceived', '设备状态变化', $postData);
        if (!empty($postData['deviceStatus'])) {
            $this->changeDeviceStatus($postData['deviceStatus'], $postData['sipDevice']['SipChannels']);
        }
        return json(['code' => self::BIS_SUCCESS_CODE, 'message' => 'AkStream onDeviceStatusReceived', 'data' => null]);
    }

    public function onInviteHistoryVideoFinished(Request $request)
    {
        return json(['code' => self::BIS_SUCCESS_CODE, 'message' => 'AkStream OnInviteHistoryVideoFinished', 'data' => $request->rawBody()]);
    }

    /**
     *
     * 写入smp_video_channels
     * @param Request $request
     * @return Response
     */
    public function onCatalogReceived(Request $request)
    {
        //写入一条新的设备目录到数据库，需激活后使用
        $postData = $this->getPostData($request);
        if (empty($postData['sipChannel']) || empty($postData['videoChannel'])) {
            return json(['code' => self::BIS_FAILED_CODE, 'message' => '非法请求', 'data' => null], JSON_UNESCAPED_UNICODE, 403);
        }

        $sipChannel = $postData['sipChannel'];
        $videoChannelFields = $postData['videoChannel'];
        $videoChannelFields['parentDeviceId'] = $sipChannel['ParentId'] ?? null;
        $videoChannelFields['VideoDeviceType'] = 'IPC';
        $videoChannelFields['enabled'] = 0;
        $videoChannelFields['local_ip_v4'] = $sipChannel['SipChannelDesc']['Address'] ?? '';
        try {
            $videoChannel = $this->getVideoChannelsService()->getVideoChannelByMainId($videoChannelFields['MainId']);
            if (!empty($videoChannel)) {
                $videoChannel = $this->getVideoChannelsService()->updateVideoChannel($videoChannel['id'], $videoChannelFields);
            } else {
                $videoChannel = $this->getVideoChannelsService()->createVideoChannel($videoChannelFields);
            }
            $this->getSystemLogService()->info('ak-hock', 'onCatalogReceived', '设备采集成功', [
                'videoChannel' => $videoChannel,
                'currentIp' => $request->getRealIp()
            ]);
        } catch (\Throwable $e) {
            $this->getSystemLogService()->info('ak-hock', 'onCatalogReceived', '设备采集失败:' . $e->getMessage(), [
                'currentIp' => $request->getRealIp()
            ]);
        }

        return json(['code' => self::BIS_SUCCESS_CODE, 'message' => 'AkStream onCatalogReceived', 'data' => null]);
    }

    protected function changeDeviceStatus(array $deviceStatus, $sipChannels)
    {
        $isNvr = false;
        try {
            $deviceStatus = new DeviceStatus(array_values($deviceStatus));
            if ($this->getVideoRecorderService()->getIsVideoRecorderDevice($deviceStatus->getDeviceID())) {
                $isNvr = true;
                $this->getVideoRecorderService()->changeStatus($deviceStatus);
                $sipChannels = array_filter($sipChannels, function ($sipChannel) use ($deviceStatus) {
                    return $sipChannel['ParentId'] === $deviceStatus->getDeviceID();
                });
            } else {
                $this->getVideoChannelsService()->changeDeviceStatus($deviceStatus);
            }
        } catch (\Exception $exception) {
            $this->getLogger()->error($exception->getMessage());
        }

        $this->batchUpdateAddrAndStatus($sipChannels, $isNvr);
    }


    protected function batchUpdateAddress($sipChannels, $isNvr)
    {
        if (empty($sipChannels)) {
            return;
        }

        $streamIds = ArrayToolkit::column($sipChannels, 'Stream');
        if (empty($streamIds)) {
            return;
        }

        $devices = $this->getVideoChannelsService()->searchVideoChannels(['mainIds' => $streamIds], [], 0, count($streamIds), ['id', 'main_id']);
        if (empty($devices)) {
            return;
        }

        echo date('Y-m-d H:i:s') . '：batchUpdateAddress', PHP_EOL;

        $this->getVideoChannelsService()->batchUpdateAddress($devices, $sipChannels, $isNvr);
    }

    protected function batchUpdateAddrAndStatus($sipChannels, $isNvr = false)
    {
        echo date('Y-m-d H:i:s') . '：batchUpdateAddrAndStatus', PHP_EOL;
        $this->getVideoChannelsService()->batchUpdateSipChannelsInfo($sipChannels, 'changeStatus', [], $isNvr);
    }

    /**
     * @param Request $request
     * @return array|null
     */
    protected function getPostData(Request $request)
    {
        $data = $request->post();
        if (empty($data)) {
            return null;
        }
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return $data;
    }

    protected function makeSign($userAgent)
    {
        $authKey = Config::get('app.ak_config.access_key');
        if (empty($authKey) || empty($userAgent)) {
            return '';
        }

        $str = sprintf("%s\n%s", $userAgent, $authKey);

        return base64_encode(strtoupper(md5($str)));
    }

    /**
     * @return \Monolog\Logger|null
     */
    protected function getLogger()
    {
        return Log::channel('aKStream-bLiveHock');
    }

    /**
     * @return DeviceActiveLogService
     */
    protected function getDeviceActiveLogService()
    {
        return $this->createService('DeviceActiveLog:DeviceActiveLogService');
    }

    /**
     * @return DeviceRegisterLogService
     */
    protected function getDeviceRegisterLogService()
    {
        return $this->createService('DeviceRegisterLog:DeviceRegisterLogService');
    }

    /**
     * @return VideoRecorderService
     */
    protected function getVideoRecorderService()
    {
        return $this->createService('VideoRecorder:VideoRecorderService');
    }

    /**
     * @return VideoChannelsService
     */
    protected function getVideoChannelsService()
    {
        return $this->createService('VideoChannels:VideoChannelsService');
    }

    /**
     * @return PlayRecordService
     */
    protected function getPlayRecordService()
    {
        return $this->createService('PlayRecord:PlayRecordService');
    }

    /**
     * @return SystemLogService
     */
    protected function getSystemLogService()
    {
        return $this->createService('SystemLog:SystemLogService');
    }

    /**
     * @return RecordService
     */
    protected function getRecordService()
    {
        return $this->createService('Record:RecordService');
    }
}