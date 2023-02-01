<?php


namespace Biz\AkStreamSdk\Resources;


interface SipServer
{

    public function historyStopVideo();


    public function historyVideo();


    public function historyVideoPosition();


    public function getHistoryRecordFileStatus();

    public function getHistoryRecordFileList();

    public function ptzCtrl(string $deviceId, string $channelId, int $ptzCommandType, int $speed);

    public function liveVideo();

    public function stopLiveVideo();

    public function isLiveVideo($deviceId, $channelId);

    public function getSipChannelById($deviceId, $channelId);

    public function getSipDeviceListByDeviceId($deviceId);

    public function getSipDeviceList();
}