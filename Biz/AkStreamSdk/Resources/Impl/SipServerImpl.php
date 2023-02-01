<?php


namespace Biz\AkStreamSdk\Resources\Impl;


use Biz\AkStreamSdk\Resources\AbstractResource;
use Biz\AkStreamSdk\Resources\ResourceBusinessException;
use Biz\AkStreamSdk\Resources\SipServer;

class SipServerImpl extends AbstractResource implements SipServer
{
    const API_PATH = 'SipGate';

    /**
     * 停止
     */
    const PTZ_STOP = 0;
    /**
     * 上
     */
    const PTZ_UP = 1;
    /**
     * 左上
     */
    const PTZ_UPLEFT = 2;
    /**
     *
     */
    const PTZ_UPRIGHT = 3;
    /**
     * 下
     */
    const PTZ_DOWN = 4;
    /**
     * 左下
     */
    const PTZ_DOWNLEFT = 5;
    /**
     * 右下
     */
    const PTZ_DOWNRIGHT = 6;
    /**
     * 左
     */
    const PTZ_LEFT = 7;
    /**
     * 右
     */
    const PTZ_RIGHT = 8;
    /**
     * 焦+
     */
    const PTZ_FOCUS1 = 9;
    /**
     * 聚焦-
     */
    const PTZ_FOCUS2 = 10;
    /**
     * 变倍+
     */
    const PTZ_ZOOM1 = 11;
    /**
     * 变倍-
     */
    const PTZ_ZOOM2 = 12;
    /**
     * 光圈开
     */
    const PTZ_IRIS1 = 13;
    /**
     * 光圈关
     */
    const PTZ_IRIS2 = 14;
    /**
     * 设置预置位
     */
    const PTZ_SETPRESET = 15;
    /**
     * 调用预置位
     */
    const PTZ_GETPRESET = 16;
    /**
     * 删除预置位
     */
    const PTZ_REMOVEPRESET = 17;
    /**
     * 未知
     */
    const PTZ_UNKNOW = 18;

    /**
     * @SubPath(path="HistroyStopVideo")
     * @return mixed
     */
    public function historyStopVideo()
    {
    }

    /**
     * @SubPath(path="HistroyVideo")
     * @return mixed
     */
    public function historyVideo()
    {
    }

    /**
     * @SubPath(path="HistroyVideoPosition")
     * @return mixed
     */
    public function historyVideoPosition()
    {
    }

    /**
     * @SubPath(path="GetHistroyRecordFileStatus")
     * @return mixed
     */
    public function getHistoryRecordFileStatus()
    {
        // TODO: Implement getHistoryRecordFileStatus() method.
    }

    /**
     * @SubPath(path="HistoryStopVideo")
     * @return mixed
     */
    public function getHistoryRecordFileList()
    {
        // TODO: Implement getHistoryRecordFileList() method.
    }

    public function ptzCtrl(string $deviceId, string $channelId, int $ptzCommandType, int $speed)
    {
        $ptzCommandTypes = array_keys($this->getPtzCmdItems());
        if (!in_array($ptzCommandType, $ptzCommandTypes)) {
            throw ResourceBusinessException::SIP_GATEWAY_PTZ_CONTROL_COMMAND_TYPE_FAILED();
        }

        if ($speed < 1) {
            throw ResourceBusinessException::SIP_GATEWAY_PTZ_CONTROL_SPEED_FAILED();
        }

        return $this->clientPost(self::API_PATH . '/PtzCtrl', [
            "ptzCommandType" => $ptzCommandType,
            "speed" => $speed,
            "deviceId" => $deviceId,
            "channelId" => $channelId,
        ]);
    }

    public function liveVideo()
    {
        // TODO: Implement liveVideo() method.
    }

    public function stopLiveVideo()
    {
        // TODO: Implement stopLiveVideo() method.
    }

    public function isLiveVideo($deviceId, $channelId)
    {
        return $this->clientGet(self::API_PATH . '/IsLiveVideo', ['deviceId' => $deviceId, 'channelId' => $channelId]);
    }

    public function getSipChannelById($deviceId, $channelId)
    {
        return $this->clientGet(self::API_PATH . '/GetSipChannelById', ['deviceId' => $deviceId, 'channelId' => $channelId]);
    }

    public function getSipDeviceListByDeviceId($deviceId)
    {
        return $this->clientGet(self::API_PATH . '/GetSipServerListByDeviceId', ['deviceId' => $deviceId]);
    }

    public function getSipDeviceList()
    {
        return $this->clientGet(self::API_PATH . '/GetSipDeviceList');
    }

    public function getPtzCmdItems()
    {
        return [
            self::PTZ_STOP => '停止',
            self::PTZ_UP => '上',
            self::PTZ_UPLEFT => '左上',
            self::PTZ_UPRIGHT => '右上',
            self::PTZ_DOWN => '下',
            self::PTZ_DOWNLEFT => '左下',
            self::PTZ_DOWNRIGHT => '右下',
            self::PTZ_LEFT => '左',
            self::PTZ_RIGHT => '右',
            self::PTZ_FOCUS1 => '聚焦+',
            self::PTZ_FOCUS2 => '聚焦-',
            self::PTZ_ZOOM1 => '变倍+',
            self::PTZ_ZOOM2 => '变倍-',
            self::PTZ_IRIS1 => '光圈开',
            self::PTZ_IRIS2 => '光圈关',
            self::PTZ_SETPRESET => '设置预置位',
            self::PTZ_GETPRESET => '调用预置位',
            self::PTZ_REMOVEPRESET => '删除预置位',
            self::PTZ_UNKNOW => '未知',
        ];
    }
}