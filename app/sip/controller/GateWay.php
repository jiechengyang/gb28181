<?php


namespace app\sip\controller;

use support\Request;
use app\sip\BaseController;
use Webman\Exception\NotFoundException;

class Gateway extends BaseController
{
    /**
     *
     * @api {GET} /sip/gateway/sipDeviceList 获取Sip设备列表
     * @akUri /SipGate/GetSipDeviceList
     *
     * @return \support\Response
     */
    public function sipDeviceList()
    {
        list($code, $data, $msg) = $this->getAkStreamSdk()->sipServer->getSipDeviceList();

        return json(['code' => $code, 'data' => $data, 'message' => $msg]);
    }

    /**
     *
     * @api {GET} /sip/gateway/historyRecordFileList
     * @akUri /SipGate/GetHistoryRecordFileList
     * 
     * @param Request $request
     */
    public function historyRecordFileList(Request $request)
    {
        list($code, $data, $msg) = $this->getAkStreamSdk()->sipServer->getHistoryRecordFileList();

        return json(['code' => $code, 'data' => $data, 'message' => $msg]);
    }

    /**
     * 
     * @api {GET} /sip/gateway/historyRecordFileStatus
     * @akUri /SipGate/GetHistoryRecordFileStatus
     *
     * @param Request $request
     * @return void
     */
    public function historyRecordFileStatus(Request $request)
    {
        list($code, $data, $msg) = $this->getAkStreamSdk()->sipServer->getHistoryRecordFileStatus();

        return json(['code' => $code, 'data' => $data, 'message' => $msg]);
    }

    /**
     *
     * @api {GET} /sip/gateway/sipChannel
     * @akUri /SipGate/GetSipChannelByDeviceId
     *
     * @param Request $request
     */
    public function sipChannel(Request $request)
    {
        $deviceId = $request->get('deviceId');
        $channelId = $request->get('channelId');
        if (empty($deviceId) || empty($channelId)) {
            throw new NotFoundException("访问不存在");
        }

        list($code, $data, $msg) = $this->getAkStreamSdk()->sipServer->getSipChannelById($deviceId, $channelId);

        return json(['code' => $code, 'data' => $data, 'message' => $msg]);
    }

    /**
     *
     * @api {GET} /sip/gateway/sipDevice
     * @akUri /SipGate/GetSipDeviceListByDeviceId
     *
     * @param Request $request
     */
    public function sipDevice(Request $request)
    {

    }

    /**
     *
     * @api {GET} /sip/gateway/systemLoggerLevel
     * @akUri /SystemApi/GetLoggerLevel
     *
     * @param Request $request
     */
    public function systemLoggerLevel(Request $request)
    {

    }

    /**
     *
     * @api {GET} /sip/gateway/systemVersion
     * @akUri /SystemApi/GetVersion
     *
     * @param Request $request
     */
    public function systemVersion(Request $request)
    {

    }

    /**
     *
     * @api {GET} /sip/gateway/systemInfo
     * @akUri /SystemApi/GetDepartmentInfoList
     *
     * @param Request $request
     */
    public function systemInfo(Request $request)
    {

    }
}