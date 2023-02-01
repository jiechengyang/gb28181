<?php

namespace Biz\VideoChannels\Service;

use Biz\GB28281\DeviceStatus;

interface VideoChannelsService
{
    /**
     * @param $id
     * @return array|null
     */
    public function getVideoChannelById($id);

    /**
     * @param array $fields
     * @return array|null
     */
    public function createVideoChannel(array $fields);

    /**
     * @param $id
     * @param array $fields
     * @return array|null
     */
    public function updateVideoChannel($id, array $fields);

    /**
     * @param $id
     * @return mixed|boolean|null|int
     */
    public function deleteVideoChannelById($id);

    public function getVideoChannelByDeviceIdAndChannelId($deviceId, $channelId);

    /**
     * @param $deviceId
     * @return array|null
     */
    public function getVideoChannelByDeviceId($deviceId);

    /**
     * @param $channelId
     * @return array|null
     */
    public function getVideoChannelByChannelId($channelId);

    /**
     * @param $mainId
     * @return array|null
     */
    public function getVideoChannelByMainId($mainId);

    public function changeDeviceStatus(DeviceStatus $deviceStatus);

    public function updateStatusOnRegisterOrUnregister($id, $status, array $timeFields);

    public function countVideoChannels(array $conditions);

    public function searchVideoChannels(array $conditions, $orderBy, $start, $limit, $columns = []);

    public function batchUpdatePartnerId(array $conditions, $parterId);

    public function batchUpdateRecorderId(array $conditions, $recorderId);

    public function activeVideoChannel($id, array $fields);

    public function batchDelete($ids);

    public function changeChannelName($id, $channelName, $syncAk = false);

    public function batchSyncDevicesControl($ids, $async = true);

    public function syncSipDevices($ids);

    public function batchLock($ids);

    public function batchUpdateSipChannelsInfo($sipChannels, $scenario = 'changeStatus', $devices = [], $dataFromNvr = false);

    public function batchUpdateAddress($devices, $sipChannels, $dataFromNvr = false);

    public function batchOpenLive($ids);

    public function batchActiveDevices($ids);

    public function batchCloseLive($ids);

    public function getMediaServerList();

    public function batchUpdateMediaServer($ids, $mediaServerId);

    public function batchUpdateAkVideoChannel($devices, $formData);

    public function batchBindRecorder($ids, $recorderId);

    public function getVideoOnlineList(array $conditions = [], $intranet = false);

    public function getVideoCover($mediaServerId, $url);


    /**
     * 云台控制
     *
     * @param string $mainId
     * @param $commandType
     * @param int $speed
     * @return mixed
     */
    public function ptzCtrl(string $mainId, $commandType, $speed = 1);

    /**
     * 获取直播地址（拉流播放）
     *
     * @param string $mainId
     * @param bool $ssl
     * @param bool $intranet
     * @return mixed
     */
    public function liveUrl(string $mainId, $ssl = false, $intranet = false);

    /**
     * 获取安全的播放地址
     *
     * @param $playUrl
     * @param $mediaServerId
     * @param $ssl
     * @return mixed
     */
    public function getSslPlayUrl($playUrl, $mediaServerId, $ssl);

    /**
     * 将内网播放地址转换公网播放地址（场景：设备与流媒体服务器在同一内网）
     *
     * @param $playUrl
     */
    public function getPublicNetPlayUrl($playUrl);

    /**
     *
     *
     * @param $recorderId
     * @param $status
     * @param $parentId 游仙项目需要检测 deviceId 是否 与recorder 的deviceId一致
     * @return mixed
     */
    public function batchUpdateDeviceStatusByRecorderId($recorderId, $status, $parentId = null);

    public function batchUpdateRtpProto($ids, $rtpProto);

    /**
     *
     * 通过gb stream id 修改通道信息
     * @param $mainId
     * @param array $fields
     * @return mixed
     */
    public function updateChannelByMainId($mainId, array $fields);

    /**
     * 批量 绑定/解绑 录像计划模板
     *
     * @param $ids
     * @param $planId 0 表示解绑
     * @return mixed
     */
    public function batchBindRecordPlan($ids, $planId = 0);

    /**
     * 批量修改 设备状态
     *
     * @param $ids
     * @param int $status
     * @return mixed
     */
    public function batchUpdateDeviceStatus($ids, $status);

    /**
     * 开启视频监控录制
     *
     * @param array $videoChannel
     * @return mixed
     */
    public function startRecord(array $videoChannel);

    /**
     * 关闭视频监控录制
     *
     * @param array $videoChannel
     * @return mixed
     */
    public function stopRecord(array $videoChannel);

    /**
     * 修改摄像头录制状态
     *
     * @param $id
     * @param int $status
     * @return mixed
     */
    public function updateRecordStatusById($id, int $status);


    /**
     * 通过心跳数据来修改设备状态
     *
     * @param $deviceId
     * @param $status
     * @return mixed
     */
    public function updateStatusWithKeepalive($deviceId, $status);
}
