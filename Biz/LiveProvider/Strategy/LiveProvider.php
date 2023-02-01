<?php


namespace Biz\LiveProvider\Strategy;


interface LiveProvider
{
    /**
     * 批量激活设备
     *
     * @param array $conditions
     * @param $sort
     * @param $offset
     * @param $limit
     * @param $options
     * @return mixed|array[]
     */
    public function activeAndOpenLiveWithCameras(array $conditions, $sort, $offset, $limit, $options = []);

    public function openLiveWithCameras(array $conditions, array $options = []);

    public function closeLiveWithCameras(array $conditions, array $options = []);

    /**
     * @param array $conditions
     * @return mixed|array[]
     */
    public function deviceTrees(array $conditions = []);

    /**
     * @param array $conditions
     * @return mixed|int|string
     */
    public function countVideoChannels(array $conditions);

    /**
     * @param array $conditions
     * @return mixed|int|string
     */
    public function countRecorders(array $conditions);

    /**
     *
     * 查询录像机列表（BLive专用，其它可参考实现）
     * @param array $conditions
     * @param $sort
     * @param $offset
     * @param $limit
     * @param array $columns
     * @return mixed
     */
    public function searchRecorders(array $conditions, $sort, $offset, $limit, $columns = []);

    /**
     *
     * 查询摄像头列表（BLive专用，其它可参考实现）
     * @param array $conditions
     * @param $sort
     * @param $offset
     * @param $limit
     * @param array $columns
     * @return mixed
     */
    public function searchCameras(array $conditions, $sort, $offset, $limit, $columns = []);

    /**
     *
     * 获取设备列表（包括：nvr ipc等）
     * @return array[]
     */
    public function getDevices();

    /**
     *
     * 获取摄像头列表
     * @return array[]
     */
    public function getCameras();

    /**
     *
     * 获取播放地址
     * @param $code
     * @param array $options
     * @return string|null
     */
    public function getLiveUrl($code, array $options = []);

    /**
     *
     * 获取 摄像头
     * @param $code
     * @return array|null
     */
    public function getCamera($code);

    public function getVideoRecorder($code);

    /**
     *
     * 开启云台控制
     * @param string $code
     * @param array $options
     * @return boolean|null|void
     */
    public function devicePtzStart(string $code, $options);

    /**
     *
     * 关闭云台控制
     * @param string $code
     * @param $options
     * @return mixed
     */
    public function devicePtzStp(string $code, $options);

    /**
     * @param $code
     * @param array $options
     * @return boolean|null|void
     */
    public function stopLive($code, array $options = []);

    /**
     *
     * 获取视频截图
     * @param $code
     * @param $protocol
     * @return string|null
     */
    public function getVideoCover($code, $protocol = '');
}