<?php


namespace app\sip\controller;

use support\Request;
use app\sip\BaseController;
use Webman\Exception\NotFoundException;

class MediaServer extends BaseController
{
    /**
     *
     * @api {GET} /sip/mediaSever/index 获取MediaServer列表
     * @akUri /MediaServer/MediaServerList
     *
     * @return \support\Response
     */
    public function index()
    {
//        list($code, $data, $msg) = $this->getAkStreamSdk()->mediaServer->getMediaServerList();

//        return json(['code' => $code, 'data' => $data, 'message' => $msg]);
        throw new NotFoundException();
    }

    public function getByMediaServerId(Request $request, $mediaServerId)
    {
//        list($code, $data, $msg) = $this->getAkStreamSdk()->mediaServer->getMediaServerByMediaServerId($mediaServerId);

//        return json(['code' => $code, 'data' => $data, 'message' => $msg]);
        throw new NotFoundException();
    }
}