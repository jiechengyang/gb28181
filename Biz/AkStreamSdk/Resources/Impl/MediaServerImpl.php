<?php


namespace Biz\AkStreamSdk\Resources\Impl;


use Biz\AkStreamSdk\Resources\AbstractResource;
use Biz\AkStreamSdk\Resources\MediaServer;
use Biz\AkStreamSdk\Resources\ResourceBusinessException;
use Biz\AkStreamSdk\Resources\Structs\ActiveVideoChannel;
use support\utils\ArrayToolkit;

class MediaServerImpl extends AbstractResource implements MediaServer
{

    const API_PATH = 'MediaServer';

    public function getMediaServerList(array $params = [])
    {
        return $this->clientGet(self::API_PATH . '/GetMediaServerList', $params);
    }

    public function getMediaServerByMediaServerId(string $mediaServerId)
    {
        if (empty($mediaServerId)) {
            throw ResourceBusinessException::MEDIA_SERVER_EMPTY_MEDIA_SERVER_ID();
        }

        return $this->clientGet(self::API_PATH . '/GetMediaServerByMediaServerId', [
            'mediaServerId' => $mediaServerId
        ]);
    }

    /**
     *  orderBy eg: {
     *     "fieldName": "id",//排序字段，具体字段详见数据库说明章节
     *     "orderByDir": 0//排序方法 0=ASC 1=DESC
     *     }
     * @param null $orderBys
     * @param int $start
     * @param int $limit
     */
    public function getWaitForActiveVideoChannelList(array $orderBys = [], int $start = 1, int $limit = 100)
    {
        $start < 1 && $start = 1;
        $limit < 1 && $limit = 10;

        empty($orderBys) && $orderBys[] = [
            "fieldName" => "id",
            "orderByDir" => 1
        ];

        return $this->clientPost(self::API_PATH . '/GetWaitForActiveVideoChannelList', [
            "pageIndex" => $start,
            "pageSize" => $limit,
            "orderBy" => $orderBys,
        ]);
    }

    /**
     *
     * php 不太适合这种方式
     * @param $mainId
     * @param ActiveVideoChannel $activeVideoChannel
     * @return array|int[]|null[]|string|null
     */
    public function activeVideoChannel(string $mainId, ActiveVideoChannel $activeVideoChannel)
    {
        return $this->clientPost(self::API_PATH . '/ActiveVideoChannel', strval($activeVideoChannel), ['mainId' => $mainId]);
    }

    /**
     * @param array $videoChannel
     * @return array|int[]|null[]|string|null
     */
    public function addVideoChannel(array $videoChannel)
    {
        $videoChannel = ArrayToolkit::parts($videoChannel, [
            "mediaServerId",
            "app",
            "vhost",
            "channelName",
            "departmentId",
            "departmentName",
            "pDepartmentId",
            "pDepartmentName",
            "deviceNetworkType",
            "deviceStreamType",
            "methodByGetStream",
            "videoDeviceType",
            "autoVideo",
            "autoRecord",
            "recordSecs",
            "recordPlanName",
            "ipV4Address",
            "ipV6Address",
            "hasPtz",
            "deviceId",
            "channelId",
            "rtpWithTcp",
            "videoSrcUrl",
            "defaultRtpPort",
            "enabled",
            "noPlayerBreak",
        ]);
        if (empty($videoChannel['mediaServerId'])) {
            throw ResourceBusinessException::MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_MEDIA_SERVERID();
        }

        if (empty($videoChannel['ipV4Address'])) {
            throw ResourceBusinessException::MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_IPV4ADDRESS();
        }

        if (empty($videoChannel['deviceId'])) {
            throw ResourceBusinessException::MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_DEVICEID();
        }

        if (empty($videoChannel['channelId'])) {
            throw ResourceBusinessException::MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_CHANNELID();
        }

        empty($videoChannel['app']) && $videoChannel['app'] = 'rtp';
        empty($videoChannel['vhost']) && $videoChannel['vhost'] = '__vhostDefault__';
        empty($videoChannel['departmentId']) && $videoChannel['departmentId'] = '';
        empty($videoChannel['departmentName']) && $videoChannel['departmentName'] = '';
        empty($videoChannel['pDepartmentId']) && $videoChannel['pDepartmentId'] = '';
        empty($videoChannel['pDepartmentName']) && $videoChannel['pDepartmentName'] = '';
        empty($videoChannel['deviceNetworkType']) && $videoChannel['deviceNetworkType'] = 'Fixed';
        empty($videoChannel['deviceStreamType']) && $videoChannel['deviceStreamType'] = 'GB28181';
        empty($videoChannel['methodByGetStream']) && $videoChannel['methodByGetStream'] = 'None';
        empty($videoChannel['videoDeviceType']) && $videoChannel['videoDeviceType'] = 'IPC';
        empty($videoChannel['autoVideo']) && $videoChannel['autoVideo'] = false;
        empty($videoChannel['autoRecord']) && $videoChannel['autoRecord'] = false;
        empty($videoChannel['recordSecs']) && $videoChannel['recordSecs'] = 0;
        empty($videoChannel['recordPlanName']) && $videoChannel['recordPlanName'] = '';
        empty($videoChannel['hasPtz']) && $videoChannel['hasPtz'] = false;
        empty($videoChannel['enabled']) && $videoChannel['enabled'] = true;
        empty($videoChannel['rtpWithTcp']) && $videoChannel['rtpWithTcp'] = false;
        empty($videoChannel['noPlayerBreak']) && $videoChannel['noPlayerBreak'] = true;

        return $this->clientPost(self::API_PATH . '/AddVideoChannel', $videoChannel);
    }

    public function modifyVideoChannel(string $mainId, array $videoChannel)
    {
        if (empty($mainId)) {
            throw ResourceBusinessException::MEDIA_SERVER_MODIFY_VIDEO_CHANNEL_EMPTY_MAIN_ID();
        }

        $videoChannel = ArrayToolkit::parts($videoChannel, ActiveVideoChannel::AkChannelKeys());


        if (empty($videoChannel['ipV4Address'])) {
            throw ResourceBusinessException::MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_IPV4ADDRESS();
        }

        if (empty($videoChannel['deviceId'])) {
            throw ResourceBusinessException::MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_DEVICEID();
        }

        if (empty($videoChannel['channelId'])) {
            throw ResourceBusinessException::MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_CHANNELID();
        }

        return $this->clientPost(self::API_PATH . '/ModifyVideoChannel', $videoChannel, ['mainId' => $mainId]);
    }

    public function deleteVideoChannel(string $mainId)
    {
        return $this->clientGet(self::API_PATH . '/DeleteVideoChannel', ['mainId' => $mainId]);
    }

    public function getVideoChannelList(array $conditions, $orderBys = [], $start = null, $limit = null)
    {
        if (!empty($conditions['createTime']) && is_numeric($conditions['createTime'])) {
            $conditions['createTime'] = date('yyyy-MM-dd HH:mm:ss', $conditions['createTime']);
        }

        if (!empty($conditions['updateTime']) && is_numeric($conditions['updateTime'])) {
            $conditions['updateTime'] = date('yyyy-MM-dd HH:mm:ss', $conditions['updateTime']);
        }

        empty($orderBys) && $orderBys[] = [
            "fieldName" => "id",
            "orderByDir" => 1
        ];

        $data = array_merge($conditions, [
            'orderBy' => $orderBys
        ]);
        $start > 1 && $data['pageIndex'] = $start;
        $limit > 1 && $data['pageSize'] = $limit;

        return $this->clientPost(self::API_PATH . '/GetVideoChannelList', $data);
    }

    public function mediaServerOpenRtpPort(string $mediaServerId, string $stream)
    {
        return $this->clientGet(self::API_PATH . '/MediaServerOpenRtpPort', [
            'mediaServerId' => $mediaServerId,
            'stream' => $stream,
        ]);
    }

    public function getOnlineStreamInfoList(array $conditions = [], array $orderBys = [], $start = 1, $limit = 100)
    {
        empty($orderBys) && $orderBys[] = ['fieldName' => 'mainId', 'orderByDir' => 1];
        $conditions = ArrayToolkit::parts($conditions, ['mediaServerId', 'mainId', 'videoChannelIp', 'StreamSourceType']);

        $data = array_merge($conditions, ['orderBy' => $orderBys, 'pageIndex' => $start, 'pageSize' => $limit]);

        return $this->clientPost(self::API_PATH . '/GetOnlineStreamInfoList', $data);
    }

    public function getRecordFileList(array $conditions = [], array $orderBys = [], $start = 1, $limit = 10)
    {
        empty($orderBys) && $orderBys[] = ['fieldName' => 'endTime', 'orderByDir' => 'DESC'];
        $data = array_merge($conditions, [
            'orderBy' => $orderBys
        ]);
        $start > 1 && $data['pageIndex'] = $start;
        $limit > 1 && $data['pageSize'] = $limit;

        return $this->clientPost(self::API_PATH . '/GetRecordFileList', $data);
    }

    public function softDeleteRecordFile(int $dbId)
    {
        return $this->clientGet(self::API_PATH . '/SoftDeleteRecordFile', ['dbId' => $dbId]);
    }

    public function restoreSoftDeleteRecordFile(int $dbId)
    {
        return $this->clientGet(self::API_PATH . '/RestoreSoftDeleteRecordFile', ['dbId' => $dbId]);
    }

    public function deleteRecordFile(int $dbId)
    {
        return $this->clientGet(self::API_PATH . '/DeleteRecordFile', ['dbId' => $dbId]);
    }

    public function deleteRecordFileList(array $dbIds)
    {
        return $this->clientPost(self::API_PATH . '/DeleteRecordFileList', ['dbIds' => $dbIds]);
    }

    public function softDeleteRecordFileList(array $dbIds)
    {
        return $this->clientPost(self::API_PATH . '/SoftDeleteRecordFileList', ['dbIds' => $dbIds]);
    }

    public function startRecord(string $mediaServerId, string $mainId)
    {
        return $this->clientGet(self::API_PATH . '/StartRecord', ['mediaServerId' => $mediaServerId, 'mainId' => $mainId]);
    }

    public function stopRecord(string $mediaServerId, string $mainId)
    {
        return $this->clientGet(self::API_PATH . '/StopRecord', ['mediaServerId' => $mediaServerId, 'mainId' => $mainId]);
    }

    public function addStreamProxy(string $mediaServerId, string $mainId)
    {
        return $this->clientGet(self::API_PATH . '/AddStreamProxy', ['mediaServerId' => $mediaServerId, 'mainId' => $mainId]);
    }

    public function addFFmpegStreamProxy(string $mediaServerId, string $mainId)
    {
        return $this->clientGet(self::API_PATH . '/AddFFmpegStreamProxy', ['mediaServerId' => $mediaServerId, 'mainId' => $mainId]);
    }

    public function streamLive(string $mediaServerId, string $mainId)
    {
        $this->client->setConnectTimeout(5);
        $this->client->setTimeout(5);
        return $this->clientGet(self::API_PATH . '/StreamLive', ['mediaServerId' => $mediaServerId, 'mainId' => $mainId]);
    }

    public function streamStop(string $mediaServerId, string $mainId)
    {
        return $this->clientGet(self::API_PATH . '/StreamStop', ['mediaServerId' => $mediaServerId, 'mainId' => $mainId]);
    }

    public function cutOrMergeVideoFile(array $fields)
    {
        $fields = ArrayToolkit::parts($fields, ['startTime', 'endTime', 'mediaServerId', 'app', 'vhost', 'callbackUrl']);
        if (!ArrayToolkit::requireds($fields, ['startTime', 'endTime', 'mediaServerId', 'app', 'vhost', 'callbackUrl'])) {
            throw ResourceBusinessException::MEDIA_SERVER_CUT_OR_MERGE_VIDEO_FILE_PARAMS_FAILED();
        }

        return $this->clientPost(self::API_PATH . '/CutOrMergeVideoFile', $fields);
    }

    public function getMergeTaskStatus(string $mediaServerId, string $taskId)
    {
        return $this->clientGet(self::API_PATH . '/GetMergeTaskStatus', ['mediaServerId' => $mediaServerId, 'taskId' => $taskId]);
    }

    public function getBacklogTaskList(string $mediaServerId)
    {
        return $this->clientGet(self::API_PATH . '/GetBacklogTaskList', ['mediaServerId' => $mediaServerId]);
    }

    public function getStreamSnap(string $liveUrl, string $mediaServerId, $timeoutSec = 0, $expireSec = 0)
    {
        return $this->clientPost(self::API_PATH . '/GetStreamSnap', [
            'url' => $liveUrl,
            'timeout_sec' => $timeoutSec,
            'expire_sec' => $expireSec
        ], ['mediaServerId' => $mediaServerId]);
    }
}