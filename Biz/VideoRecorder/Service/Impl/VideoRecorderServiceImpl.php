<?php

namespace Biz\VideoRecorder\Service\Impl;

use Biz\BaseService;

use Biz\GB28281\DeviceStatus;
use Biz\GB28281\DeviceTypeEnum;
use Biz\SystemLog\Service\SystemLogService;
use Biz\VideoChannels\Service\VideoChannelsService;
use Biz\VideoRecorder\Exception\VideoRecorderException;
use Biz\VideoRecorder\Service\VideoRecorderService;
use Biz\VideoRecorder\Dao\VideoRecorderDao;
use support\utils\ArrayToolkit;

class VideoRecorderServiceImpl extends BaseService implements VideoRecorderService
{

    public function changeStatusWithKeepalive($deviceId, $status, $originIpc = true)
    {
        $nvr = $this->getVideoRecorderByDeviceId($deviceId);
        if (empty($nvr)) {
            return false;
        }

        $params['status'] = $status;
        if ($params['status'] === DeviceStatus::STATUS_ONLINE) {
            $params['lastOnlineTime'] = time();
        } else {
            $params['lastOfflineTime'] = time();
        }
        $this->getVideoRecorderDao()->update($nvr['id'], $params);
        if ($originIpc) {
            $this->getVideoChannelsService()->batchUpdateDeviceStatusByRecorderId($nvr['id'], $status, $deviceId);
        }
    }

    public function batchUpdateParterId(array $conditions, $parterId)
    {
        return $this->getVideoRecorderDao()->update($conditions, ['parter_id' => $parterId]);
    }

    public function countRecorders(array $conditions)
    {
        return $this->getVideoRecorderDao()->count($conditions);
    }

    public function searchRecorders(array $conditions, $orderBy, $start, $limit, $columns = [])
    {
        return $this->getVideoRecorderDao()->search($conditions, $orderBy, $start, $limit, $columns);
    }

    public function getVideoRecorderById($id)
    {
        return $this->getVideoRecorderDao()->get($id);
    }

    public function createVideoRecorder(array $fields)
    {
        return $this->getVideoRecorderDao()->create($fields);
    }

    public function updateVideoRecorder($id, array $fields)
    {
        return $this->getVideoRecorderDao()->update($id, $fields);
    }

    public function deleteVideoRecorderById($id)
    {
        return $this->getVideoRecorderDao()->delete($id);
    }

    public function syncVideoRecorder(array $sizDevice, $type = 'registerReceived')
    {
        if (
            empty($sizDevice['DeviceStatus'])
            || empty($sizDevice['DeviceInfo'])
            || empty($sizDevice['DeviceInfo']['DeviceID'])
        ) {
            $this->createNewException(VideoRecorderException::SIZ_DEVICE_PARAMS_FAILED());
        }

        $deviceInfo = $sizDevice['DeviceInfo'];
        $deviceStatus = $sizDevice['DeviceStatus'];
        $deviceType = $this->getDeviceTypeCodeByDeviceId($deviceInfo['DeviceID']);
        $oldRecorder = $this->getVideoRecorderByDeviceId($deviceInfo['DeviceID']);
        $fields = [
            'device_id' => $deviceInfo['DeviceID'],
            'device_sn' => $deviceInfo['SN'],
            'type_code' => $deviceType[0],
            'manufacturer' => $deviceInfo['Manufacturer'],
            'device_model' => $deviceInfo['Model'],
            'firmware' => $deviceInfo['Firmware'],
            'channel_num' => $this->countVideoChannel($sizDevice['SipChannels']),//$deviceInfo['Channel'],
            'username' => $sizDevice['Username'],
            'password' => $sizDevice['Password'],
            'local_ip' => $sizDevice['IpAddress'],
            'local_sip_port' => $sizDevice['Port'],
            'status' => $deviceStatus['Online'] === "ONLINE" ? DeviceStatus::STATUS_ONLINE : DeviceStatus::STATUS_OFFLINE,
        ];
        if ($fields['status'] === DeviceStatus::STATUS_ONLINE) {
            $fields['lastOnlineTime'] = time();
        } else {
            $fields['lastOfflineTime'] = time();
        }

        // 如果是录像机
        if ($this->getIsVideoRecorderDevice($deviceInfo['DeviceID'])) {
            if (!empty($oldRecorder)) {
                $result = $this->updateVideoRecorder($oldRecorder['id'], $fields);
                if ($fields['status'] === DeviceStatus::STATUS_OFFLINE) {
                    // 不在线 可认为摄像头也不在线
                    $this->getVideoChannelsService()->batchUpdateDeviceStatusByRecorderId($oldRecorder['id'], $fields['status'], $oldRecorder['device_id']);
                } else {
                    // 在线 就无法确认摄像头是否在线，需要判断通道信息
                    $channels = $sizDevice['SipChannels'] ?? [];
                    if (!empty($channels)) {
//                        $channels = array_filter($channels, function($channel) use ($deviceInfo) {
//                           return $channel['ParentId'] == $deviceInfo['DeviceID'];
//                        });
                        $this->getVideoChannelsService()->batchUpdateSipChannelsInfo($channels, 'changeStatus', [], true);
                    }
                }

                return $result;
            }

            return 'registerReceived' === $type ? $this->createVideoRecorder($fields) : true;
        }

        // 针对摄像头处理
        if ($this->getIsCameraDevice($deviceInfo['DeviceID'])) {
            if (!empty($sizDevice['SipChannels'][0]['Stream'])) {
                if ($fields['status'] === DeviceStatus::STATUS_ONLINE) {
                    $params['lastOnlineTime'] = time();
                } else {
                    $params['lastOfflineTime'] = time();
                }
                $params['device_status'] = $fields['status'];
                $this->getVideoChannelsService()->updateChannelByMainId($sizDevice['SipChannels'][0]['Stream'], $params);
            }
        }
    }


    /**
     * @param $deviceId
     * @return bool
     */
    public function getIsVideoRecorderDevice($deviceId)
    {
        $deviceTypeCode = substr($deviceId, 10, 3);
        $videoRecordeEnums = DeviceTypeEnum::videoRecorderEnums();

        return isset($videoRecordeEnums[$deviceTypeCode]);
    }

    /**
     *
     * 是否是摄像头
     * @param $deviceId
     * @return bool
     */
    public function getIsCameraDevice($deviceId)
    {
        $deviceTypeCode = substr($deviceId, 10, 3);
        $cameraEnums = DeviceTypeEnum::cameraEnums();

        return isset($cameraEnums[$deviceTypeCode]);
    }

    public function getVideoRecorderByDeviceId($deviceId)
    {
        return $this->getVideoRecorderDao()->findByDeviceId($deviceId);
    }

    public function changeStatus(DeviceStatus $deviceStatus)
    {
        $videoRecorder = $this->getVideoRecorderByDeviceId($deviceStatus->getDeviceID());
        if (empty($videoRecorder)) {
            //createNewException
            $this->createNewException(VideoRecorderException::VIDEO_RECORDER_NOT_FOUND());
        }

        $fields = ['status' => $deviceStatus->getStatusCode()];
        if ($deviceStatus->getIsOnline()) {
            $fields['lastOnlineTime'] = $deviceStatus->getKeepTime();
        } else {
            $fields['lastOfflineTime'] = $deviceStatus->getKeepTime();
        }
        try {
            $videoRecorder = $this->getVideoRecorderDao()->update($videoRecorder['id'], $fields);
            //$this->getSystemLogService()->info('VideoRecorder', 'changeStatus', '同步录像机状态', $fields);

            return $videoRecorder;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function countVideoChannel($sipChannels)
    {
        if (!is_array($sipChannels)) {
            return 0;
        }

        $channels = array_filter($sipChannels, function ($sipChannel) {
            return $sipChannel['SipChannelType'] === 'VideoChannel';
        });

        return count($channels);
    }

    /**
     * @return SystemLogService
     */
    protected function getSystemLogService()
    {
        return $this->createService('SystemLog:SystemLogService');
    }

    /**
     * @return VideoChannelsService
     */
    protected function getVideoChannelsService()
    {
        return $this->createService('VideoChannels:VideoChannelsService');
    }

    /**
     * @return VideoRecorderDao
     */
    protected function getVideoRecorderDao()
    {
        return $this->createDao('VideoRecorder:VideoRecorderDao');
    }
}
