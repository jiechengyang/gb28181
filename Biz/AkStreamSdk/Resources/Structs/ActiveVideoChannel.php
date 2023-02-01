<?php


namespace Biz\AkStreamSdk\Resources\Structs;

/**
 *
 *
 * Class ActiveVideoChannel
 * @package Biz\AkStreamSdk\Resources\Structs
 */
class ActiveVideoChannel
{
    public $mediaServerId;
    public $app = "rtp";
    public $vhost = "__defaultVhost__";
    public $channelName;
    public $departmentId = "";
    public $departmentName = "";
    public $pDepartmentId = "";
    public $pDepartmentName = "";
    public $deviceNetworkType = "Fixed";
    public $videoDeviceType = "IPC";
    public $deviceStreamType = 'GB28181';
    public $methodByGetStream = 'None';
    /**
     * @var bool 是否自动启用推拉流;改成按需点播；一直播放:autoVideo=true;noPlayerBreak=false;
     */
    public $autoVideo = false;
    /**
     * @var bool 是否自动启用录制计划
     */
    public $autoRecord = false;
    public $recordSecs = 0;
    public $recordPlanName = null;
    public $ipV4Address;
    public $ipV6Address;
    public $hasPtz = false;
    public $rtpWithTcp = true;
    public $defaultRtpPort = false;
    /**
     * @var bool 人观察时断开流端口，此字段为true时AutoVideo字段必须为false,如果AutoVideo为true,则此字段无效
     */
    public $noPlayerBreak = true;

    public $fFmpegTemplate = null;

    public $videoSrcUrl = '';

    public $isShareChannel = false;

    public $shareUrl = '';

    public $shareDeviceId = '';

    /**
     *
     * 当 args[0] 为 init时，不设置类的属性值
     * ActiveVideoChannel constructor.
     * @param array $args
     */
    public function __construct(array $args)
    {
        if (isset($args[0]) && 'init' === $args[0]) {
            return;
        }

        if (count($args) !== count(array_keys(get_object_vars($this)))) {
            $str = json_encode($args);
            throw new \InvalidArgumentException("ActiveVideoChannel args failed:$str");
        }

        foreach ($args as $key => $value) {
            if (!property_exists($this, $key)) {
                continue;
            }
            $this->{$key} = $value;
        }
    }

    public function __toString()
    {
        return json_encode(get_object_vars($this));
    }

    public function dbChannelToAkChannel($dbChannel)
    {
        return [
            'app' => $dbChannel['app'] ?? $this->app,
            'vhost' => $dbChannel['vhost'] ?? $this->vhost,
            'channelName' => $dbChannel['channel_name'],
            'departmentId' => $dbChannel['dept_id'] ?? $this->departmentId,
            'departmentName' => $dbChannel['dept_name'] ?? $this->departmentName,
            'pDepartmentId' => $dbChannel['parent_dept_id'] ?? $this->pDepartmentId,
            'pDepartmentName' => $dbChannel['parent_dept_name'] ?? $this->pDepartmentName,
            'deviceNetworkType' => $dbChannel['device_network_type'] ?? $this->deviceNetworkType,
            'videoDeviceType' => $dbChannel['video_device_type'] ?? $this->videoDeviceType,
            'autoVideo' => $dbChannel['auto_video'] ?? $this->autoVideo,
            'autoRecord' => $dbChannel['auto_record'] ?? $this->autoRecord,
            'recordSecs' => $dbChannel['record_secs'] ?? $this->recordSecs,
            'recordPlanName' => $dbChannel['record_plan_name'] ?? $this->recordPlanName,
            'ipV4Address' => $dbChannel['ip_v4_address'],
            'ipV6Address' => $dbChannel['ip_v6_address'],
            'hasPtz' => isset($dbChannel['has_ptz']) ? (boolean)$dbChannel['has_ptz'] : $this->hasPtz,
            'rtpWithTcp' => $this->rtpWithTcp,//isset($dbChannel['rtp_proto']) && 'udp' === $dbChannel['rtp_proto'] ? false :
            'defaultRtpPort' => isset($dbChannel['default_rtp_port']) ? (boolean)$dbChannel['default_rtp_port'] : $this->defaultRtpPort,
            'noPlayerBreak' => isset($dbChannel['np_player_break']) ? (boolean)$dbChannel['np_player_break'] : $this->noPlayerBreak,
            'fFmpegTemplate' => $dbChannel['ffmpeg_template'] ?? $this->fFmpegTemplate,
            'enabled' => (boolean)$dbChannel['enabled'],
            'mediaServerId' => $dbChannel['media_server_id'],
            'deviceStreamType' => $dbChannel['device_stream_type'] ?? $this->deviceStreamType,
            'methodByGetStream' => 'None',//$dbChannel['method_by_get_stream'] ?? $this->methodByGetStream,
            'videoSrcUrl' => $dbChannel['video_src_url'] ?? $this->videoSrcUrl,
            'deviceId' => $dbChannel['device_id'],
            'channelId' => $dbChannel['channel_id'],
            'isShareChannel' => isset($dbChannel['is_share_channel']) ? (boolean)$dbChannel['is_share_channel'] : $this->isShareChannel,
            'shareUrl' => $dbChannel['share_url'] ?? $this->shareUrl,
            'shareDeviceId' => $dbChannel['share_device_id'] ?? $this->shareDeviceId,
        ];
    }

    public static function AkChannelKeys()
    {
        return [
            'app',
            'vhost',
            'channelName',
            'departmentId',
            'departmentName',
            'pDepartmentId',
            'pDepartmentName',
            'deviceNetworkType',
            'videoDeviceType',
            'autoVideo',
            'autoRecord',
            'recordSecs',
            'recordPlanName',
            'ipV4Address',
            'ipV6Address',
            'hasPtz',
            'rtpWithTcp',
            'defaultRtpPort',
            'noPlayerBreak',
            'fFmpegTemplate',
            'enabled',
            'mediaServerId',
            'deviceStreamType',
            'methodByGetStream',
            'videoSrcUrl',
            'deviceId',
            'channelId',
            'isShareChannel',
            'shareUrl',
            'shareDeviceId',
        ];
    }
}