<?php


namespace Biz\LiveProvider\Strategy\Impl;


use Biz\AkStreamSdk\AkStreamSdk;
use Biz\AkStreamSdk\Resources\Structs\ActiveVideoChannel;
use Biz\Constants;
use Biz\LiveProvider\Strategy\LiveProvider;
use Biz\LiveProvider\Strategy\LiveProviderStrategy;
use Biz\VideoChannels\Exception\VideoChannelsException;
use Biz\VideoChannels\Service\VideoChannelsService;
use Biz\VideoRecorder\Service\VideoRecorderService;
use \Exception;
use support\Request;
use support\utils\ArrayToolkit;
use Webman\Config;

class BLiveStrategy extends LiveProviderStrategy implements LiveProvider
{
    /**
     *
     * 获取视频截图(成功返回base64串）
     * @param $code
     * @param $protocol
     * @return string|null
     */
    public function getVideoCover($code, $protocol = '')
    {
        $camera = $this->getCamera($code);
        if (empty($camera)) {
            return null;
        }

        if ($camera['auto_live'] && Config('app.ak_config.zlm_local_host') && $camera['device_status'] == 1) {
            // TODO: 如果配置zlm内网地址且开启了自动推流的摄像头就自行拼接rtmp地址
            $host = Config('app.ak_config.zlm_local_host');
            $port = Config('app.ak_config.zlm_rtmp_port');
            $url = "rtmp://{$host}:{$port}/rtp/{$camera['main_id']}";
        } else {
            $url = $this->getLiveUrl($code, ['protocol' => $protocol, 'transformHost' => false]);
        }

        $mediaServerId = $camera['media_server_id'];
        $cover = $this->getVideoChannelsService()->getVideoCover($mediaServerId, $url);
        if (empty($cover)) {
            return null;
        }

        return sprintf("data:image/jpeg;base64,%s", $cover);
    }

    /**
     * 国标直播流默认是是一直直播，既NoPlayerBreak为false
     *
     * @param array $conditions
     * @param $sort
     * @param $offset
     * @param $limit
     * @param array $options
     * @return int[]
     * @throws Exception
     */
    public function activeAndOpenLiveWithCameras(array $conditions, $sort, $offset, $limit, $options = [])
    {
        !isset($conditions['enabled']) && $conditions['enabled'] = 0;
        $videoChannels = $this->getVideoChannelsService()->searchVideoChannels($conditions, $sort, $offset, $limit);
        if (empty($videoChannels)) {
            throw new Exception("未查询到可激活的摄像头");
        }

        if (empty($options['mediaServerId'])) {
            $options['mediaServerId'] = $this->getDefaultMediaServerId();
        }

        if (empty($options['mediaServerId'])) {
            throw new \InvalidArgumentException("参数错误:[流媒体服务id必须提供]");
        }

        $successCount = 0;
        $failedCount = 0;
        foreach ($videoChannels as $videoChannel) {
            $id = $videoChannel['id'];
            $videoChannel = array_merge($options, $videoChannel);
            list($code, $result, $msg) = $this->activeVideoChannel($videoChannel['main_id'], $videoChannel);
            // -1021 表示ak已经激活 0 表示第一次激活
            if (0 === $code) {
                $successCount++;
                $this->getVideoChannelsService()->activeVideoChannel($id, [
                    'media_server_id' => $videoChannel['mediaServerId'],
                    'has_ptz' => (int)$result['hasPtz'],
                    'method_by_get_stream' => $result['methodByGetStream'],
                    'rtp_proto' => $videoChannel['rtpWithTcp'] ? 'tcp' : 'udp'
                ]);
            } elseif (-1021 === $code) {
                $successCount++;
                $this->getVideoChannelsService()->activeVideoChannel($id, [
                    'media_server_id' => $videoChannel['mediaServerId'],
                    'has_ptz' => 0,
                    'method_by_get_stream' => 'None',
                    'rtp_proto' => $videoChannel['rtpWithTcp'] ? 'tcp' : 'udp'
                ]);
            } else {
                $failedCount++;
            }
        }

        return ['successCount' => $successCount, 'failedCount' => $failedCount];
    }

    public function openLiveWithCameras(array $conditions, array $options = [])
    {
        !isset($conditions['enabled']) && $conditions['enabled'] = 1;
        $cameras = $this->getVideoChannelsService()->searchVideoChannels($conditions, [], 0, PHP_INT_MAX);
        $count = 0;
        $ids = [];
        foreach ($cameras as $camera) {
            list($code, $result, $message) = $this->getAkStreamSdk()->mediaServer->streamLive($camera['media_server_id'], $camera['main_id']);
            if ($result) {
                $count++;
                $ids[] = $camera['id'];
            }
        }

        $autoVideo = $options['autoVideo'] ?? false;
        $this->getVideoChannelsService()->batchUpdateAkVideoChannel($cameras, ['autoVideo' => $autoVideo, 'closeLive' => false]);

        return $count;
    }

    public function closeLiveWithCameras(array $conditions, array $options = [])
    {
        !isset($conditions['enabled']) && $conditions['enabled'] = 1;
        $cameras = $this->getVideoChannelsService()->searchVideoChannels($conditions, [], 0, PHP_INT_MAX);
        $count = 0;
        foreach ($cameras as $camera) {
            list($code, $result, $message) = $this->getAkStreamSdk()->mediaServer->streamStop($camera['media_server_id'], $camera['main_id']);
            if ($result) {
                $count++;
            }
        }

        $closeAutoVideo = $options['closeAutoVideo'] ?? false;
        if ($closeAutoVideo) {
            $this->getVideoChannelsService()->batchUpdateAkVideoChannel($cameras, ['autoVideo' => false, 'closeLive' => true]);
        }

        return $count;
    }

    public function deviceTrees(array $conditions = [])
    {
        $recorders = $this->getVideoRecorderService()->searchRecorders($conditions, ['id' => 'ASC'], 0, PHP_INT_MAX, ['id', 'device_name', 'device_id', 'status', 'channel_num', 'local_ip', 'net_ip']);
        if (!empty($recorders)) {
            $conditions['recorderIds'] = ArrayToolkit::column($recorders, 'id');
        }

        array_unshift($conditions['recorderIds'], 0);
        $recorders[] = [
            'id' => 0,
            'device_name' => '未绑定',
            'device_id' => '--',
            'status' => 0,
            'channel_num' => 0,
            'local_ip' => '--',
            'net_ip' => '--',
        ];
        $videoChannels = $this->getVideoChannelsService()->searchVideoChannels($conditions, ['id' => 'ASC'], 0, PHP_INT_MAX);
        $videoChannelsWithRecord = ArrayToolkit::group($videoChannels, 'recorder_id');
        foreach ($recorders as &$recorder) {
            if (isset($videoChannelsWithRecord[$recorder['id']])) {
                $recorder['subCameras'] = $videoChannelsWithRecord[$recorder['id']];
            }
        }

        return $recorders;
    }

    public function countRecorders(array $conditions)
    {
        return $this->getVideoRecorderService()->countRecorders($conditions);
    }

    public function searchRecorders(array $conditions, $sort, $offset, $limit, $columns = [])
    {
        return $this->getVideoRecorderService()->searchRecorders($conditions, $sort, $offset, $limit, $columns);
    }

    public function countVideoChannels(array $conditions)
    {
        return $this->getVideoChannelsService()->countVideoChannels($conditions);
    }

    public function searchCameras(array $conditions, $sort, $offset, $limit, $columns = [])
    {
        return $this->getVideoChannelsService()->searchVideoChannels($conditions, $sort, $offset, $limit, $columns);
    }

    public function getDevices()
    {
        // TODO: Implement getDevices() method.
    }

    public function getCameras()
    {
        // TODO: Implement getCameras() method.
    }

    public function getLiveUrl($code, array $options = [])
    {
        $options = ArrayToolkit::parts($options, ['protocol', 'expireTime', 'quality', 'ssl', 'transformHost', 'intranet']);
        empty($options['protocol']) && $options['protocol'] = Constants::BLIVE_STREAM_PROTOCOL_HLS;
        $videoChannel = $this->getVideoChannelsService()->getVideoChannelByMainId($code);
        if (empty($videoChannel)) {
            throw VideoChannelsException::VIDEO_CHANNEL_NOT_FOUND();
        }

        if ((int)$videoChannel['enabled'] !== 1) {
            throw VideoChannelsException::VIDEO_CHANNEL_NOT_ACTIVE();
        }

        if ('all' !== $options['protocol'] && !array_key_exists($options['protocol'], Constants::getBLiveStreamProtocolItems())) {
            throw VideoChannelsException::VIDEO_CHANNEL_STREAM_LIVE_PROTOCOL_NOT_FOUND();
        }

        list($code, $result, $msg) = $this->getAkStreamSdk()->mediaServer->streamLive($videoChannel['media_server_id'], $videoChannel['main_id']);
        if ($code !== 0 || empty($result['playUrl'])) {
            throw  VideoChannelsException::VIDEO_CHANNEL_STREAM_LIVE_FAILED();
        }

        $transformHost = isset($options['transformHost']) ? $options['transformHost'] : true;
        if (isset($options['intranet']) && $options['intranet'] === true) {
            $transformHost = false;
        }

        if ($options['protocol'] === 'all') {
            if ($transformHost) {
                foreach ($result['playUrl'] as &$url) {
                    $url = $this->getPublicNetPlayUrl($url);
                    $url = $this->getSslPlayUrl($url, $videoChannel['media_server_id'], $options['ssl'] ?? false);
                }

            } else {
                foreach ($result['playUrl'] as &$url) {
                    $url = $this->getSslPlayUrl($url, $videoChannel['media_server_id'], $options['ssl'] ?? false);
                }

            }

            return $result['playUrl'];
        }

        $url = $this->streamLiveUrlFilter($result['playUrl'], $options['protocol']);

        if ($transformHost) {
            $url = $this->getPublicNetPlayUrl($url);
        }

        return $this->getSslPlayUrl($url, $videoChannel['media_server_id'], $options['ssl'] ?? false);
    }

    public function stopLive($code, array $options = [])
    {
        $videoChannel = $this->getVideoChannelsService()->getVideoChannelByMainId($code);
        if (empty($videoChannel)) {
            throw VideoChannelsException::VIDEO_CHANNEL_NOT_FOUND();
        }

        if ((int)$videoChannel['enabled'] !== 1) {
            throw VideoChannelsException::VIDEO_CHANNEL_NOT_ACTIVE();
        }

        $mediaServerId = $options['mediaServerId'] ?? $videoChannel['media_server_id'];

        list($code, $result, $msg) = $this->getAkStreamSdk()->mediaServer->streamStop($mediaServerId, $videoChannel['main_id']);
        if ($code !== 0 || !$result) {
            throw  VideoChannelsException::VIDEO_CHANNEL_STREAM_LIVE_FAILED();
        }

        return true;
    }

    public function getCamera($code)
    {
        if (empty($code)) {
            return null;
        }

        if (is_numeric($code)) {
            return $this->getVideoChannelsService()->getVideoChannelById($code);
        }

        $videoChannel = $this->getVideoChannelsService()->getVideoChannelByMainId($code);
        if (!empty($videoChannel)) {
            return $videoChannel;
        }

        return $this->getVideoChannelsService()->getVideoChannelByDeviceId($code);
    }

    public function getVideoRecorder($code)
    {
        if (empty($code)) {
            return null;
        }

        if (is_numeric($code)) {
            return $this->getVideoRecorderService()->getVideoRecorderById($code);
        }

        return $this->getVideoRecorderService()->getVideoRecorderByDeviceId($code);
    }

    /**
     * @param string $code
     * @param array $options
     * @return bool|void|null
     */
    public function devicePtzStart(string $code, $options)
    {
        if (!ArrayToolkit::requireds($options, ['speed']) || !isset($options['ptzCommandType'])) {
            throw  VideoChannelsException::VIDEO_CHANNEL_PTZ_CONTROL_PARAMS_FAILED();
        }

        $videoChannel = $this->getVideoChannelsService()->getVideoChannelByMainId($code);
        if (empty($videoChannel)) {
            throw VideoChannelsException::VIDEO_CHANNEL_NOT_FOUND();
        }

        try {
            $result = $this->getAkStreamSdk()->sipServer->ptzCtrl($videoChannel['device_id'], $videoChannel['channel_id'], $options['ptzCommandType'], $options['speed']);
            $this->getSystemLogService()->info('sip_ptz_control', 'ptz_control_success', '云台控制操作成功');
            return $result;
        } catch (Exception $exception) {
            $this->getSystemLogService()->info('sip_ptz_control', 'ptz_control_failed', '云台控制操作失败');
            throw $exception;
        }

    }

    /**
     *
     * 关闭云台控制
     * @param string $code
     * @param $options
     * @return mixed
     */
    public function devicePtzStp(string $code, $options)
    {
        if (!ArrayToolkit::requireds($options, ['speed']) || !isset($options['ptzCommandType'])) {
            throw  VideoChannelsException::VIDEO_CHANNEL_PTZ_CONTROL_PARAMS_FAILED();
        }

        $videoChannel = $this->getVideoChannelsService()->getVideoChannelByMainId($code);
        if (empty($videoChannel)) {
            throw VideoChannelsException::VIDEO_CHANNEL_NOT_FOUND();
        }

        try {
            $result = $this->getAkStreamSdk()->sipServer->ptzCtrl($videoChannel['device_id'], $videoChannel['channel_id'], 0, $options['speed']);
            $this->getSystemLogService()->info('sip_ptz_control', 'ptz_control_success', '关闭云台控制操作成功');
            return $result;
        } catch (Exception $exception) {
            $this->getSystemLogService()->info('sip_ptz_control', 'ptz_control_failed', '关闭云台控制操作失败');
            throw $exception;
        }

    }

    public function activeVideoChannel($mainId, &$videoChannel)
    {
        $videoChannel = $this->dbVideoChannelToSipVideoChannel($videoChannel);
        $activeVideoChannel = new ActiveVideoChannel($videoChannel);
        return $this->getAkStreamSdk()->mediaServer->activeVideoChannel($mainId, $activeVideoChannel);
    }

    /**
     * 将内网播放地址转换公网播放地址（场景：设备与流媒体服务器在同一内网）
     *
     * @param $playUrl
     */
    protected function getPublicNetPlayUrl($playUrl)
    {
        return $this->getVideoChannelsService()->getPublicNetPlayUrl($playUrl);
    }

    protected function getSslPlayUrl($playUrl, $mediaServerId, $ssl)
    {
        return $this->getVideoChannelsService()->getSslPlayUrl($playUrl, $mediaServerId, $ssl);
    }

    protected function getDefaultMediaServerId()
    {
        list($code, $mediaServers, $msg) = $this->getAkStreamSdk()->mediaServer->getMediaServerList();
        if (0 !== $code || empty($mediaServers)) {
            throw new Exception("未查询到流媒体服务器:[{$code}->{$msg}]");
        }

        $defaultMediaServer = array_shift($mediaServers);

        return $defaultMediaServer['mediaServerId'];
    }

    protected function dbVideoChannelToSipVideoChannel($dbVideoChannel)
    {
        return [
            'mediaServerId' => $dbVideoChannel['mediaServerId'],
            'app' => $dbVideoChannel['app'],
            'vhost' => $dbVideoChannel['vhost'],
            'channelName' => $dbVideoChannel['channel_name'],
            'departmentId' => $dbVideoChannel['dept_id'],
            'departmentName' => $dbVideoChannel['dept_name'],
            'pDepartmentId' => $dbVideoChannel['parent_dept_id'],
            'pDepartmentName' => $dbVideoChannel['parent_dept_name'],
            'deviceNetworkType' => $dbVideoChannel['device_network_type'],
            'videoDeviceType' => $dbVideoChannel['video_device_type'],
            'deviceStreamType' => 'GB28281',
            'methodByGetStream' => 'None',
            'autoVideo' => false, // 换成按需点播，带宽遭不住;一直直播用：true
            'noPlayerBreak' => true,
            'autoRecord' => false,
            'recordSecs' => 0,
            'recordPlanName' => null,
            'ipV4Address' => $dbVideoChannel['ip_v4_address'],
            'ipV6Address' => $dbVideoChannel['ip_v6_address'],
            'hasPtz' => true,
            'rtpWithTcp' => $dbVideoChannel['rtp_proto'] === 'tcp', //$dbVideoChannel['rtpWithTcp'] ?? true,
            'defaultRtpPort' => false,
            'fFmpegTemplate' => null,
            'videoSrcUrl ' => '',
            'isShareChannel' => false,
            'shareUrl' => '',
            'shareDeviceId' => '',
        ];
    }

    /**
     * @param $playUrls
     * @param string $protocol
     * @return string
     */
    protected function streamLiveUrlFilter($playUrls, $protocol = 'hls')
    {
        $liveUrl = '';
        $protocol = explode('_', $protocol);
        foreach ($playUrls as $playUrl) {
            if (count($protocol) === 1) {
                if (false !== strpos($playUrl, $protocol[0])) {
                    $liveUrl = $playUrl;
                    break;
                }
            } else {
                if (false !== strpos($playUrl, $protocol[0]) && false !== strpos($playUrl, $protocol[1])) {
                    $liveUrl = $playUrl;
                    break;
                }
            }
        }

        return $liveUrl;
    }

    /**
     * @return \support\bootstrap\Redis
     */
    protected function getRedis()
    {
        return $this->biz->offsetGet('redis.api.cache');
    }

    /**
     * @return AkStreamSdk
     */
    protected function getAkStreamSdk()
    {
        return $this->biz->offsetGet('sip.ak_stream_sdk');
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
}