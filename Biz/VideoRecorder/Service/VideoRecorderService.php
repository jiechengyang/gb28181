<?php

namespace Biz\VideoRecorder\Service;

use Biz\GB28281\DeviceStatus;

interface VideoRecorderService
{
    public function getVideoRecorderById($id);

    public function createVideoRecorder(array $fields);

    public function updateVideoRecorder($id, array $fields);

    public function deleteVideoRecorderById($id);

    public function syncVideoRecorder(array $sizDevice, $type = 'registerReceived');

    public function getVideoRecorderByDeviceId($deviceId);

    public function changeStatus(DeviceStatus $deviceStatus);

    /**
     * @param $deviceId
     * @return bool
     */
    public function getIsVideoRecorderDevice($deviceId);

    public function countRecorders(array $conditions);

    public function searchRecorders(array $conditions, $orderBy, $start, $limit, $columns = []);

    public function batchUpdateParterId(array $conditions, $parterId);

    /**
     * 通过心跳数据来修改设备状态
     * 录像机注册时间为24个小时，而检测设备状态任务进程是通过判断上线时间是否超过2个小时，此情况下，如果设备不掉线则录像机下的摄像头都会被设置成离线
     * @param $deviceId
     * @param $status
     * @param  $originIpc 是否关联ipc设备修改，默认是
     * @return mixed
     */
    public function changeStatusWithKeepalive($deviceId, $status, $originIpc = true);
}
