<?php

namespace Biz\VideoChannels\Service\Impl;

use Biz\AkStreamSdk\AkStreamSdk;
use Biz\AkStreamSdk\Resources\Structs\ActiveVideoChannel;
use Biz\BaseService;
use Biz\Constants;
use Biz\GB28281\DeviceStatus;
use Biz\LiveProvider\LiveProviderFactory;
use Biz\LiveProvider\Strategy\LiveProvider;
use Biz\Setting\Service\SettingService;
use Biz\SystemLog\Service\SystemLogService;
use Biz\VideoChannels\Exception\VideoChannelsException;
use Biz\VideoChannels\Service\VideoChannelsService;
use Biz\VideoChannels\Dao\VideoChannelsDao;
use Biz\VideoRecorder\Service\VideoRecorderService;
use Exception;
use support\utils\ArrayToolkit;
use Webman\Config;
use Webman\RedisQueue\Client;

/**
 *
 * @todo 调用ak接口的方法最好不要同步调用
 * Class VideoChannelsServiceImpl
 * @package Biz\VideoChannels\Service\Impl
 */
class VideoChannelsServiceImpl extends BaseService implements VideoChannelsService
{
    public function updateStatusWithKeepalive($deviceId, $status)
    {
        $ipc = $this->getVideoChannelByDeviceId($deviceId);
        if (empty($ipc)) {
            return false;
        }
        $fields = ['device_status' => $status];
        if ($status === DeviceStatus::STATUS_ONLINE) {
            $fields['lastOnlineTime'] = time();
//            $fields['record_status'] = Constants::VIDEO_CHANNEL_RECORD_STATUS_NONE;
        } else {
            $fields['lastOfflineTime'] = time();
            $fields['record_status'] = Constants::VIDEO_CHANNEL_RECORD_STATUS_CLOSE;
        }

        return $this->getVideoChannelsDao()->update($ipc['id'], $fields);
    }

    /**
     * 开启视频监控录制
     *
     * @param array $videoChannel
     * @return mixed
     */
    public function startRecord(array $videoChannel)
    {
        // 判断参数
        list($code, $result, $message) = $this->getAkStreamSdk()->mediaServer->startRecord($videoChannel['media_server_id'], $videoChannel['main_id']);
        //写入日志
        $this->getSystemLogService()->info('video-channels', 'start-record', $message, $result);
        if ($code == 0) {
            // 更新摄像头录制状态
            $this->getVideoChannelsDao()->update($videoChannel['id'], ['record_status' => 1]);
        }
        return $code;
    }

    /**a
     * 修改摄像头录制状态
     *
     * @param $id
     * @param int $status
     * @return mixed
     */
    public function updateRecordStatusById($id, int $status)
    {
        $this->getVideoChannelsDao()->update($id, ['record_status' => $status]);
    }

    /**
     * 关闭视频监控录制
     *
     * @param array $videoChannel
     * @return mixed
     */
    public function stopRecord(array $videoChannel)
    {
        // 判断参数
        list($code, $result, $message) = $this->getAkStreamSdk()->mediaServer->stopRecord($videoChannel['media_server_id'], $videoChannel['main_id']);
        //写入日志
        $this->getSystemLogService()->info('video-channels', 'stop-record', $message, $result);
        if ($code == 0) {
            // 更新摄像头录制状态
            $this->getVideoChannelsDao()->update($videoChannel['id'], ['record_status' => 0]);
        }
        return $code;
    }

    /**
     *
     * 通过gb stream id 修改通道信息
     * @param $mainId
     * @param array $fields
     * @return mixed
     */
    public function updateChannelByMainId($mainId, array $fields)
    {
        return $this->getVideoChannelsDao()->update(['mainId' => $mainId], $fields);
    }

    public function batchUpdateRtpProto($ids, $rtpProto)
    {
        if (empty($ids)) {
            return false;
        }

        $rtpProto = strtolower($rtpProto);
        $devices = $this->getVideoChannelsDao()->findInByIds($ids);
        $ids = ArrayToolkit::column($devices, 'id');
        $result = $this->getVideoChannelsDao()->update(['ids' => $ids], ['rtp_proto' => $rtpProto]);
        if ($result) {
            Client::send('sip:update-channel', [
                'devices' => $devices,
                'formData' => [
                    'rtpWithTcp' => $rtpProto === 'tcp'
                ]
            ]);
        }
        return;
    }

    public function batchUpdateDeviceStatusByRecorderId($recorderId, $status, $parentId = null)
    {
        $fields = ['device_status' => $status];
        if ($status === DeviceStatus::STATUS_ONLINE) {
            $fields['lastOnlineTime'] = time();
//            $fields['record_status'] = Constants::VIDEO_CHANNEL_RECORD_STATUS_NONE;
        } else {
            $fields['lastOfflineTime'] = time();
            $fields['record_status'] = Constants::VIDEO_CHANNEL_RECORD_STATUS_CLOSE;
        }

        $conditions = ['recorderId' => $recorderId];
        if (!empty($parentId)) {
            $conditions['deviceId'] = $parentId;
        }

        return $this->getVideoChannelsDao()->update($conditions, $fields);
    }

    public function liveUrl(string $mainId, $ssl = false, $intranet = false)
    {
        $videoChannel = $this->getVideoChannelByMainId($mainId);
        if (empty($videoChannel)) {
            return [];
        }

        list($code, $result, $msg) = $this->getAkStreamSdk()->mediaServer->streamLive($videoChannel['media_server_id'], $videoChannel['main_id']);
        if ($code !== 0 || empty($result['playUrl'])) {
            throw  VideoChannelsException::VIDEO_CHANNEL_STREAM_LIVE_FAILED();
        }

        if (!$intranet) {
            foreach ($result['playUrl'] as &$url) {
                $url = $this->getPublicNetPlayUrl($url);
            }
        }


        if ($ssl) {
            foreach ($result['playUrl'] as &$url) {
                $url = $this->getSslPlayUrl($url, $videoChannel['media_server_id'], true);
            }
        }

        return $result['playUrl'];
    }

    public function ptzCtrl(string $mainId, $commandType, $speed = 2)
    {
        $videoChannel = $this->getVideoChannelByMainId($mainId);
        if (empty($videoChannel)) {
            return false;
        }

        list($code, $result, $msg) = $this->getAkStreamSdk()->sipServer->ptzCtrl($videoChannel['device_id'], $videoChannel['channel_id'], $commandType, $speed);
        if ($result) {
            usleep(1000000 * 0.2);
            list($stopCode, $stopResult, $stopMsg) = $this->getAkStreamSdk()->sipServer->ptzCtrl($videoChannel['device_id'], $videoChannel['channel_id'], 0, $speed);
        }

        return $result;
    }

    public function getVideoCover($mediaServerId, $url)
    {
        // TODO: 这里固定把url host 换成 127.0.0.1,因为sip-media-manage 和 zlm在同一台服务器；如果不是一台服务器这里就不能这样写
        $host = parse_url($url, PHP_URL_HOST);
        $url = preg_replace("/{$host}/", "127.0.0.1", $url);
        // TODO：优化，增加video_channel_cover表，使用定时任务采集图片
        list($code, $cover, $message) = $this->getAkStreamSdk()->mediaServer->getStreamSnap($url, $mediaServerId, 10, 30);
        usleep(1000000 * 0.2);

        return empty($cover) ? null : $cover;
    }

    public function getVideoOnlineList(array $conditions = [], $intranet = false)
    {
        list($code, $result, $message) = $this->getAkStreamSdk()->mediaServer->getOnlineStreamInfoList($conditions);
        if (empty($result['videoChannelMediaInfo'])) {
            return [];
        }

        $onlineList = [];
        foreach ($result['videoChannelMediaInfo'] as $item) {
            if (empty($item['mediaServerStreamInfo'])) {
                continue;
            }

            $mediaServerStreamInfo = $item['mediaServerStreamInfo'];
            if (!empty($mediaServerStreamInfo['playUrl']) && !$intranet) {
                foreach ($mediaServerStreamInfo['playUrl'] as &$playUrl) {
                    $playUrl = $this->getPublicNetPlayUrl($playUrl);
                }
            }

            $row = [
                'media_server_id' => $item['mediaServerId'],
                'start_time' => $mediaServerStreamInfo['startTime'],
                'main_id' => $item['mainId'],
                'play_url' => $mediaServerStreamInfo['playUrl'],
                'push_socket_type' => $mediaServerStreamInfo['pushSocketType'],
                'device_id' => $item['deviceId'],
                'channel_id' => $item['channelId'],
                'channel_name' => $item['channelName'],
                'rpt_port' => $mediaServerStreamInfo['rptPort'],
            ];
            $onlineList[] = $row;
        }

        return $onlineList;
    }

    public function batchBindRecorder($ids, $recorderId)
    {
        if (empty($ids)) {
            return false;
        }

        $devices = $this->getVideoChannelsDao()->findInByIds($ids);
        $ids = ArrayToolkit::column($devices, 'id');

        return $this->getVideoChannelsDao()->update(['ids' => $ids], ['recorder_id' => $recorderId]);
    }

    public function batchBindRecordPlan($ids, $planId = 0)
    {
        if (empty($ids)) {
            return false;
        }

        $devices = $this->getVideoChannelsDao()->findInByIds($ids);
        $ids = ArrayToolkit::column($devices, 'id');

        return $this->getVideoChannelsDao()->update(['ids' => $ids], ['record_plan_id' => $planId]);
    }

    public function batchUpdateDeviceStatus($ids, $status)
    {
        if (empty($ids)) {
            return false;
        }

        $data = ['device_status' => $status];
        if ($status == Constants::DEVICE_STATUS_OFFLINE) {
            $data['lastOfflineTime'] = time();
            $data['record_status'] = Constants::VIDEO_CHANNEL_RECORD_STATUS_CLOSE;
            // 设备如果掉线就要将已经再录像的摄像头停止录像
        } elseif ($status == Constants::DEVICE_STATUS_ONLINE) {
            $data['lastOnlineTime'] = time();
//            $data['record_status'] = Constants::VIDEO_CHANNEL_RECORD_STATUS_NONE;
        }

        return $this->getVideoChannelsDao()->update(['ids' => $ids], $data);
    }


    public function batchUpdateAkVideoChannel($devices, $formData)
    {
        if (empty($devices) || empty($formData)) {
            return;
        }

        $ids = ArrayToolkit::column($devices, 'id');
        if (isset($formData['closeLive'])) {
            $closeLive = $formData['closeLive'] ? 1 : 0;
            $data = ['close_live' => $closeLive];
            if ($closeLive) {
                $data['auto_live'] = 0;
            }

            $this->getVideoChannelsDao()->update(['ids' => $ids], $data);
        }

        $formData = ArrayToolkit::parts($formData, ActiveVideoChannel::AkChannelKeys());
        $activeVideoChannel = new ActiveVideoChannel(['init']);
        if (isset($formData['autoVideo']) && $formData['autoVideo']) {
            $formData['noPlayerBreak'] = false;
        }

        foreach ($devices as $device) {
            $channel = $activeVideoChannel->dbChannelToAkChannel($device);
            $channel = array_merge($channel, $formData);
            list($code, $result, $message) = $this->getAkStreamSdk()->mediaServer->modifyVideoChannel($device['main_id'], $channel);
            if (!empty($result)) {
                $this->getSystemLogService()->info('video-channels', 'update-channel-success', ' 修改音视频通道实例参数成功', ['result' => $result, 'channel' => $channel]);
            } else {
                $this->getSystemLogService()->info('video-channels', 'update-channel-error', ' 修改音视频通道实例参数失败', ['result' => $result, 'channel' => $channel, 'code' => $code, 'message' => $message]);
            }
        }
    }

    public function batchUpdateMediaServer($ids, $mediaServerId)
    {
        if (empty($ids)) {
            return false;
        }

        $devices = $this->getVideoChannelsDao()->findInByIds($ids);
        $updateDevices = array_filter($devices, function ($device) use ($mediaServerId) {
            return $device['media_server_id'] != $mediaServerId;
        });

        if (empty($updateDevices)) {
            return true;
        }

        $updateIds = ArrayToolkit::column($updateDevices, 'id');
        $result = $this->getVideoChannelsDao()->update(['ids' => $updateIds], ['media_server_id' => $mediaServerId, 'close_live' => 0]);
        if ($result) {
            Client::send('sip:update-channel', [
                'devices' => $devices,
                'formData' => [
                    'mediaServerId' => $mediaServerId
                ]
            ]);
        }

        return $result;
    }

    public function getMediaServerList()
    {
        list($code, $mediaServers, $msg) = $this->getAkStreamSdk()->mediaServer->getMediaServerList();
        if (empty($mediaServers)) {
            return [];
        }

        return array_column($mediaServers, 'mediaServerId', 'mediaServerId');
    }

    public function batchSyncDevicesControl($ids, $async = true)
    {
        if ($async) {
            Client::send('sync:sip-device', ['ids' => $ids]);
            return true;
        }

        $this->syncSipDevices($ids);
        return true;
    }

    public function syncSipDevices($ids)
    {
        list($code, $sipDeviceList, $message) = $this->getAkStreamSdk()->sipServer->getSipDeviceList();
        if (empty($sipDeviceList)) {
            throw new Exception("同步失败:[{$code}]->[{$message}]");
        }

        $devices = $this->searchVideoChannels(['ids' => $ids], [], 0, PHP_INT_MAX, ['id', 'main_id', 'device_id', 'channel_id', 'las']);
        $this->batchUpdateSipChannelsInfo($sipDeviceList, 'syncSipDevices', $devices);
    }

    public function batchCloseLive($ids)
    {
        if (empty($ids)) {
            return false;
        }

        Client::send('ipc:close-live', ['ids' => $ids]);

        return true;
    }

    public function batchOpenLive($ids)
    {
        if (empty($ids)) {
            return false;
        }

        $this->getVideoChannelsDao()->update(['ids' => $ids], ['close_live' => 0, 'auto_live' => 1]);
        Client::send('ipc:open-live', ['ids' => $ids]);

        return true;
    }

    public function batchActiveDevices($ids)
    {
        if (empty($ids)) {
            return false;
        }

        Client::send('ipc:active', ['ids' => $ids]);

        return true;
    }

    public function batchLock($ids)
    {
        if (empty($ids)) {
            return false;
        }

        return $this->getVideoChannelsDao()->update(['ids' => $ids], ['locked' => 1]);
    }

    public function batchDelete($ids)
    {
        if (empty($ids)) {
            return false;
        }

        return $this->getVideoChannelsDao()->batchDelete(['ids' => $ids]);
    }

    public function batchUpdateRecorderId(array $conditions, $recorderId)
    {
        return $this->getVideoChannelsDao()->update($conditions, ['recorder_id' => $recorderId]);
    }

    public function batchUpdatePartnerId(array $conditions, $parterId)
    {
        return $this->getVideoChannelsDao()->update($conditions, ['parter_id' => $parterId]);
    }

    public function searchVideoChannels(array $conditions, $orderBy, $start, $limit, $columns = [])
    {
        return $this->getVideoChannelsDao()->search($conditions, $orderBy, $start, $limit, $columns);
    }

    public function countVideoChannels(array $conditions)
    {
        return $this->getVideoChannelsDao()->count($conditions);
    }

    public function changeDeviceStatus(DeviceStatus $deviceStatus)
    {
        $videoChannel = $this->getVideoChannelsDao()->findByDeviceId($deviceStatus->getDeviceID());
        if (empty($videoChannel)) {
            $this->createNewException(VideoChannelsException::VIDEO_CHANNEL_NOT_FOUND());
        }

        $fields = ['device_status' => $deviceStatus->getStatusCode()];
        if ($deviceStatus->getIsOnline()) {
            $fields['lastOnlineTime'] = $deviceStatus->getKeepTime();
            $fields['record_status'] = $videoChannel['record_status'] == Constants::VIDEO_CHANNEL_RECORD_STATUS_ING ? Constants::VIDEO_CHANNEL_RECORD_STATUS_ING : Constants::VIDEO_CHANNEL_RECORD_STATUS_NONE;
        } else {
            $fields['lastOfflineTime'] = $deviceStatus->getKeepTime();
            $fields['record_status'] = Constants::VIDEO_CHANNEL_RECORD_STATUS_CLOSE;
        }

        try {
            $videoChannel = $this->getVideoChannelsDao()->update($videoChannel['id'], $fields);
//            $this->getSystemLogService()->info('VideoChannels', 'changeStatus', '同步摄像头状态', [
//                'data' => strval($deviceStatus),
//            ]);

            return $videoChannel;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getVideoChannelById($id)
    {
        return $this->getVideoChannelsDao()->get($id);
    }

    public function createVideoChannel(array $fields)
    {
        if (!ArrayToolkit::requireds($fields, ['MainId', 'Vhost', 'DeviceStreamType', 'IpV4Address', 'DeviceId', 'ChannelId'])) {
            $this->createNewException(VideoChannelsException::VIDEO_CHANNEL_REGISTER_FIELDS_FAILED());
        }

        $fields = $this->filterFields($fields);


        return $this->getVideoChannelsDao()->create($fields);
    }

    public function updateStatusOnRegisterOrUnregister($id, $status, array $timeFields)
    {
        $timeFields['device_status'] = $status;

        return $this->getVideoChannelsDao()->update($id, $timeFields);
    }

    public function updateVideoChannel($id, array $fields)
    {
        if (!ArrayToolkit::requireds($fields, ['MainId', 'Vhost', 'DeviceStreamType', 'IpV4Address', 'DeviceId', 'ChannelId'])) {
            $this->createNewException(VideoChannelsException::VIDEO_CHANNEL_REGISTER_FIELDS_FAILED());
        }

        $fields = $this->filterFields($fields);
        $fields = ArrayToolkit::parts($fields, ['media_server_id', 'vhost', 'app', 'channel_name', 'device_network_type', 'video_device_type', 'ip_v4_address', 'ip_v6_address', 'has_ptz', 'rtp_proto', 'default_rtp_port', 'dept_id', 'dept_name', 'parent_dept_name', 'lastOnlineTime', 'device_status', 'recorder_id', 'parter_id']);

        return $this->getVideoChannelsDao()->update($id, $fields);
    }

    public function changeChannelName($id, $channelName, $syncAk = false)
    {
        return $this->getVideoChannelsDao()->update($id, [
            'channel_name' => $channelName
        ]);
    }

    public function activeVideoChannel($id, array $fields)
    {
        if (!ArrayToolkit::requireds($fields, ['media_server_id'])) {
            $this->createNewException(VideoChannelsException::VIDEO_CHANNEL_ACTIVE_FIELDS_FAILED());
        }

        $fields = ArrayToolkit::parts($fields, ['media_server_id', 'has_ptz', 'method_by_get_stream', 'rtp_proto']);
        $fields['enabled'] = 1;

        return $this->getVideoChannelsDao()->update($id, $fields);
    }

    public function batchUpdateAddress($devices, $sipChannels, $dataFromNvr = false)
    {
        if (empty($devices) || empty($sipChannels)) {
            return false;
        }

        $message = '【从摄像头接收的数据报文】批量更新IP地址';
        if ($dataFromNvr) {
            $message = '【从录像机接收的数据报文】批量更新IP地址';
        }

        $this->getSystemLogService()->info('videoChannels', 'batchUpdateAddress', $message, $sipChannels);
        $channels = [];
        foreach ($sipChannels as $item) {
            if (empty($item['Stream']) || $item['SipChannelType'] !== 'VideoChannel') {
                continue;
            }
            $sipChannel = $item['SipChannelDesc'];
            $channels[$item['Stream']]['address'] = $sipChannel['Address'];
        }


        $ids = [];
        $updateRows = [];
        foreach ($devices as $device) {
            if (!isset($channels[$device['main_id']])) {
                continue;
            }

            $channel = $channels[$device['main_id']];
            $row['local_ip_v4'] = $this->filterAddress($channel['address'] ?? '--');
            if (empty($row['local_ip_v4'])) {
                continue;
            }
            $updateRows[] = $row;
            $ids[] = $device['id'];
        }

        if (empty($ids)) {
            return false;
        }

        return $this->getVideoChannelsDao()->batchUpdate($ids, $updateRows);
    }

    public function batchUpdateSipChannelsInfo($sipChannels, $scenario = 'changeStatus', $devices = [], $dataFromNvr = false)
    {
        if (empty($sipChannels)) {
            return;
        }

        $message = '【从摄像头接收的数据报文】批量更新设备通道信息';
        if ($dataFromNvr) {
            $message = '【从录像机接收的数据报文】批量更新设备通道信息';
        }
//        $this->getSystemLogService()->info('videoChannels', 'batchUpdateSipChannelsInfo', $message, [
//            'channels' => $sipChannels,
//            'scenario' => $scenario
//        ]);
        $channels = [];
        if ('changeStatus' === $scenario) {
            $streamIds = ArrayToolkit::column($sipChannels, 'Stream');
            if (empty($streamIds)) {
                return;
            }

            if (empty($devices)) {
                $devices = $this->searchVideoChannels(['mainIds' => $streamIds], [], 0, count($streamIds), ['id', 'main_id', 'lastOfflineTime', 'record_status']);
            }

            if (empty($devices)) {
                return;
            }
            foreach ($sipChannels as $item) {
                if (!isset($item['Stream']) || $item['SipChannelType'] !== 'VideoChannel') {
                    continue;
                }
                $sipChannel = $item['SipChannelDesc'];
                $channels[$item['Stream']] = $sipChannel;
                $channels[$item['Stream']]['deviceId'] = $item['ParentId'];
                $channels[$item['Stream']]['channelId'] = $item['DeviceId'];
                $channels[$item['Stream']]['address'] = $sipChannel['Address'];
                $channels[$item['Stream']]['status'] = $sipChannel['Status'];
            }
        } else {
            foreach ($sipChannels as $item) {
                foreach ($item['sipChannels'] as $sipChannel) {
                    if (!isset($sipChannel['stream'])) {
                        continue;
                    }
                    $channels[$sipChannel['stream']] = $sipChannel['sipChannelDesc'];
                }
            }
        }

        return $this->syncVideoChannelsOnChangeChange($channels, $devices);
    }

    protected function filterAddress($address)
    {
//        $address = str_replace("\u0000", ".", $address);
//        $address = str_replace("�", ".", $address);
        if ($address === 'Address') {
            $address = '';
        }

        if (empty($address)) {
            return '';
        }

        preg_match_all("/[\x{00}-\x{ff}]/u", $address, $matches);
        $str = "";
        if (!empty($matches[0])) {
            foreach ($matches[0] as $match) {
                if (is_numeric($match)) {
                    $str .= $match;
                } else {
                    $str .= '.';
                }
            }
        }

        return $str ?? $address;
    }

    /**
     *
     * 设备通道发生变化时同步通道
     * @param $sipChannels ak 收到的通道
     * @param $devices 系统已经存在的设备通道
     * @return bool
     */
    protected function syncVideoChannelsOnChangeChange($sipChannels, $devices)
    {
        if (empty($devices)) {
            return false;
        }

        $updateOnlineRows = [];
        $updateOnlineIds = [];
        $updateOfflineRows = [];
        $updateOfflineIds = [];
        $addRows = [];
        $devices = ArrayToolkit::index($devices, 'main_id');
        foreach ($sipChannels as $mainId => $channel) {
            if (!isset($devices[$mainId])) {
                $channel['main_id'] = $mainId;
                $addRows[] = $channel;
            } else {
                $row = [];
                $device = $devices[$mainId];
                $row['local_ip_v4'] = $this->filterAddress($channel['address'] ?? '--');
                $row['device_status'] = $channel['status'] === 'ON' ? DeviceStatus::STATUS_ONLINE : DeviceStatus::STATUS_OFFLINE;
                if (DeviceStatus::STATUS_ONLINE === $row['device_status']) {
                    $row['lastOnlineTime'] = time();
                    $row['lastOfflineTime'] = 0;
                    // 如果在录像中，就不能改变状态
                    $row['record_status'] = $device['record_status'] == Constants::VIDEO_CHANNEL_RECORD_STATUS_ING ? Constants::VIDEO_CHANNEL_RECORD_STATUS_ING : Constants::VIDEO_CHANNEL_RECORD_STATUS_NONE;
                    $updateOnlineRows[] = $row;
                    $updateOnlineIds[] = $device['id'];
                } else {
                    // 首次离线记录离线时间
                    $row['lastOnlineTime'] = 0;
                    if (empty($device['lastOfflineTime'])) {
                        $row['lastOfflineTime'] = time();
                    } else {
                        $row['lastOfflineTime'] = $device['lastOfflineTime'];
                    }
                    $row['record_status'] = Constants::VIDEO_CHANNEL_RECORD_STATUS_CLOSE;
                    $updateOfflineRows[] = $row;
                    $updateOfflineIds[] = $device['id'];
                }
            }
        }


        if (!empty($updateOnlineIds)) {
            $this->getVideoChannelsDao()->batchUpdate($updateOnlineIds, $updateOnlineRows);
        }


        if (!empty($updateOfflineIds)) {
            $this->getVideoChannelsDao()->batchUpdate($updateOfflineIds, $updateOfflineRows);
        }

        if (!empty($addRows)) {
            $this->batchCreateWithSipChannels($addRows);
        }

        return true;
    }

    /**
     *
     *
     * @param $sipChannels
     */
    protected function batchCreateWithSipChannels($sipChannels)
    {
        $rows = [];
        foreach ($sipChannels as $sipChannel) {
            if (empty($sipChannel['main_id']) || empty($sipChannel['channelId'])) {
                continue;
            }
            $address = $this->filterAddress($sipChannel['address'] ?? '');
            if (empty($address) || $address === '0.0.0.0') {
                continue;
            }

            $fields = [
                'main_id' => $sipChannel['main_id'],
                'media_server_id' => 'unknown',
                'vhost' => '__defaultVhost__',
                'app' => 'rtp',
                'channel_name' => '---',
                'device_network_type' => 1,
                'device_stream_type' => 0,
                'video_device_type' => 3,
                'ip_v4_address' => $address,
                'ip_v6_address' => '',
                'has_ptz' => 0,
                'device_id' => $sipChannel['deviceId'],
                'channel_id' => $sipChannel['channelId'],
                'rtp_proto' => 'tcp',
                'default_rtp_port' => 10000,
                'dept_id' => '',
                'dept_name' => '',
                'parent_dept_id' => '',
                'parent_dept_name' => '',
                'enabled' => 0,
                'createdTime' => time(),
                'updatedTime' => time(),
            ];
            if ($sipChannel['status'] === 'ON') {
                $fields['device_status'] = DeviceStatus::STATUS_ONLINE;
                $fields['lastOnlineTime'] = time();
                $fields['lastOfflineTime'] = 0;
//                $fields['record_status'] = Constants::VIDEO_CHANNEL_RECORD_STATUS_NONE;

            } else {
                $fields['device_status'] = DeviceStatus::STATUS_OFFLINE;
                $fields['lastOnlineTime'] = 0;
                $fields['lastOfflineTime'] = time();
                $fields['record_status'] = Constants::VIDEO_CHANNEL_RECORD_STATUS_CLOSE;
            }
            $fields['local_ip_v4'] = $fields['ip_v4_address'];
            $rows[] = $fields;
        }

        $this->getVideoChannelsDao()->batchCreate($rows);
    }

    protected function filterFields(array $fields)
    {
        $params = [
            'main_id' => $fields['MainId'],
            'media_server_id' => 'unknown',
            'vhost' => $fields['Vhost'] ?? '__defaultVhost__',
            'app' => $fields['App'],
            'channel_name' => strpos($fields['ChannelName'], "�") !== false ? '---' : $fields['ChannelName'],
            'device_network_type' => $fields['DeviceNetworkType'],
            'device_stream_type' => $fields['DeviceStreamType'],
            'video_device_type' => $fields['VideoDeviceType'],
            'ip_v4_address' => $fields['IpV4Address'],
            'ip_v6_address' => $fields['IpV6Address'],
            'has_ptz' => (int)$fields['HasPtz'],
            'device_id' => $fields['DeviceId'],
            'channel_id' => $fields['ChannelId'],
            'rtp_proto' => $fields['RtpWithTcp'] ? 'tcp' : 'udp',
            'default_rtp_port' => $fields['DefaultRtpPort'] ? 10000 : 0,
            'dept_id' => $fields['DepartmentId'],
            'dept_name' => $fields['DepartmentName'],
            'parent_dept_id' => $fields['PDepartmentId'],
            'parent_dept_name' => $fields['PDepartmentName'],
            'lastOnlineTime' => time(),
            'enabled' => 0,
            'device_status' => DeviceStatus::STATUS_ONLINE,
        ];
        if (!empty($fields['parentDeviceId'])) {
            $this->fillRecorderFields($fields['parentDeviceId'], $params);
        }

        if (empty($fields['recorder_id'])) {
            $this->fillRecorderFields($params['device_id'], $params);
        }

        return $params;
    }

    public function deleteVideoChannelById($id)
    {
        return $this->getVideoChannelsDao()->delete($id);
    }

    public function getVideoChannelByDeviceIdAndChannelId($deviceId, $channelId)
    {
        return $this->getVideoChannelsDao()->findByDeviceIdAndChannelId($deviceId, $channelId);
    }


    public function getVideoChannelByDeviceId($deviceId)
    {
        return $this->getVideoChannelsDao()->findByDeviceId($deviceId);
    }

    public function getVideoChannelByChannelId($channelId)
    {
        return $this->getVideoChannelsDao()->findByChannelId($channelId);
    }

    public function getVideoChannelByMainId($mainId)
    {
        return $this->getVideoChannelsDao()->findByMainId($mainId);
    }

    public function getSslPlayUrl($playUrl, $mediaServerId, $ssl)
    {
        if (strpos($playUrl, 'https://') !== false) {
            return $playUrl;
        }

        if (strpos($playUrl, 'http://') === false) {
            return $playUrl;
        }

        if (!$ssl) {
            return $playUrl;
        }

        $akServiceConfig = $this->getSettingService()->getAkServerConfig();
        if (!empty($akServiceConfig['media_server_proxy'][$mediaServerId])) {
            $proxyUrl = $akServiceConfig['media_server_proxy'][$mediaServerId];
            $startIndex = strpos($playUrl, '/rtp');

            return $proxyUrl . substr($playUrl, $startIndex);
        }

        return $playUrl;

    }

    /**
     * 将内网播放地址转换公网播放地址（场景：设备与流媒体服务器在同一内网）
     *
     * @param $playUrl
     */
    public function getPublicNetPlayUrl($playUrl)
    {
        $publicHost = Config::get('app.ak_config.zlm_public_host');
        if (empty($publicHost)) {
            return $playUrl;
        }

        $host = parse_url($playUrl, PHP_URL_HOST);
        $localIps = Config::get('app.ak_config.zlm_local_ips');
        if (empty($localIps)) {
            return $playUrl;
        }
        $localIps = explode('|', $localIps);
        if ($this->isLocalClient($host, $localIps)) {
            return preg_replace("/{$host}/", $publicHost, $playUrl);
        }

        return $playUrl;
    }

    /**
     * 判断是否为局域网访问
     * @param $ip
     * @param $ipFilters
     * @return bool
     */
    protected function isLocalClient($ip, $ipFilters)
    {
        return \is_local_client($ip, $ipFilters);
    }

    protected function fillRecorderFields($deviceId, &$fields)
    {
        $videoRecorder = $this->getVideoRecorderService()->getVideoRecorderByDeviceId($deviceId);
        if (!empty($videoRecorder)) {
            $fields['recorder_id'] = $videoRecorder['id'];
            $fields['parter_id'] = $videoRecorder['parter_id'];
            $device = $this->getVideoChannelByDeviceIdAndChannelId($deviceId, $fields['channel_id']);
            if (!empty($device)) {
                // TODO: 证明录像机已经收录了这台ipc，那么需要把这个设备关联到录像机收录的那条数据;同时后台需要禁区分录像机那台设备
                $fields['origin_main_id'] = $device['main_id'];
            }
        }
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
     * @return VideoChannelsDao
     */
    protected function getVideoChannelsDao()
    {
        return $this->createDao('VideoChannels:VideoChannelsDao');

    }

    /**
     * @return SystemLogService
     */
    protected function getSystemLogService()
    {
        return $this->createService('SystemLog:SystemLogService');
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->createService('Setting:SettingService');
    }
}
