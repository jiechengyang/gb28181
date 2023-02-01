<?php


namespace Biz\AkStreamSdk\Resources;


use Biz\AkStreamSdk\Resources\Structs\ActiveVideoChannel;

/**
 *
 * Interface MediaServer
 *
 * --- 常用参数 ---
 * @param string mediaServerId 定流媒体服务器
 * @param string mainId 音视频流通道id
 * @param string $stream 指定此Rtp服务器绑定的streamid
 * @wiki https://github.com/chatop2020/AKStream/wiki/MediaServer%E7%B1%BB%E6%8E%A5%E5%8F%A3%E8%AF%B4%E6%98%8E
 * @package Biz\AkStreamSdk\Resources
 */
interface MediaServer
{

    /**
     * @return mixed
     */
    public function getMediaServerList();

    /**
     * @param string $mediaServerId
     * @return mixed
     */
    public function getMediaServerByMediaServerId(string $mediaServerId);

    /**
     * @param array $orderBys
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getWaitForActiveVideoChannelList(array $orderBys, int $start = 1, int $limit = 100);

    /**
     * @param string $mainId
     * @param ActiveVideoChannel $activeVideoChannel
     * @return mixed
     */
    public function activeVideoChannel(string $mainId, ActiveVideoChannel $activeVideoChannel);

    /**
     * @param array $videoChannel
     * @return mixed
     */
    public function addVideoChannel(array $videoChannel);

    /**
     * @param string $mainId mainId为音视频通道在数据库中的唯一id
     * @param array $videoChannel
     * @return mixed
     */
    public function modifyVideoChannel(string $mainId, array $videoChannel);

    /**
     * @param string $mainId 指定的是要对哪个音视频通道进行删除，mainId为音视频通道在数据库中的唯一id
     * @return mixed
     */
    public function deleteVideoChannel(string $mainId);

    /**
     * @param array $conditions
     * @param array $orderBys
     * @param null $start
     * @param null $limit
     * @return mixed
     */
    public function getVideoChannelList(array $conditions, $orderBys = [], $start = null, $limit = null);

    /**
     * @param string $mediaServerId 指定向哪个流媒体服务器申请开放Rtp服务器
     * @param string $stream 指定此Rtp服务器绑定的streamid
     * @return mixed
     */
    public function mediaServerOpenRtpPort(string $mediaServerId, string $stream);

    /**
     * @param array $conditions
     * @param array $orderBys
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getOnlineStreamInfoList(array $conditions = [], array $orderBys = [], $start = 1, $limit = 100);


    /**
     * @param array $conditions
     * @param array $orderBys
     * @param int $start
     * @param int $limit
     * @return mixed
     */
    public function getRecordFileList(array $conditions = [], array $orderBys = [], $start = 1, $limit = 10);


    /**
     * @param int $dbId
     * @return mixed
     */
    public function softDeleteRecordFile(int $dbId);

    /**
     * @param int $dbId
     * @return mixed
     */
    public function restoreSoftDeleteRecordFile(int $dbId);

    /**
     * @param int $dbId
     * @return mixed
     */
    public function deleteRecordFile(int $dbId);

    /**
     * @param array $dbIds
     * @return mixed
     */
    public function deleteRecordFileList(array $dbIds);

    /**
     * @param array $dbIds
     * @return mixed
     */
    public function softDeleteRecordFileList(array $dbIds);

    /**
     * @param string $mediaServerId 流媒体服务器的id
     * @param string $mainId 音视频流通道id
     * @return mixed
     */
    public function startRecord(string $mediaServerId, string $mainId);

    /**
     * @param string $mediaServerId
     * @param string $mainId
     * @return mixed
     */
    public function stopRecord(string $mediaServerId, string $mainId);


    /**
     * @param string $mediaServerId
     * @param string $mainId
     * @return mixed
     */
    public function addStreamProxy(string $mediaServerId, string $mainId);


    /**
     * @param string $mediaServerId
     * @param string $mainId
     * @return mixed
     */
    public function addFFmpegStreamProxy(string $mediaServerId, string $mainId);


    /**
     * @param string $mediaServerId
     * @param string $mainId
     * @return mixed
     */
    public function streamLive(string $mediaServerId, string $mainId);


    /**
     * @param string $mediaServerId
     * @param string $mainId
     * @return mixed
     */
    public function streamStop(string $mediaServerId, string $mainId);


    /**
     * @param array $fields
     * @return mixed
     */
    public function cutOrMergeVideoFile(array $fields);

    /**
     * @param string $mediaServerId
     * @param string $taskId 创建添加任务时返回的任务id
     * @return mixed
     */
    public function getMergeTaskStatus(string $mediaServerId, string $taskId);

    /**
     * @param string $mediaServerId
     * @return mixed
     */
    public function getBacklogTaskList(string $mediaServerId);

    /**
     *
     * 获取一帧视频帧
     * @param string $liveUrl
     * @param string $mediaServerId
     * @param int $timeoutSec
     * @param int $expireSec
     * @return mixed
     */
    public function getStreamSnap(string $liveUrl, string $mediaServerId, $timeoutSec = 0, $expireSec = 0);
}