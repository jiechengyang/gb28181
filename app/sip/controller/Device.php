<?php


namespace app\sip\controller;


use app\sip\BaseController;
use app\sip\filters\VideoChannelFilter;
use app\sip\filters\VideoRecorderFilter;
use Biz\DataFilters\Filter;
use Biz\VideoChannels\Service\VideoChannelsService;
use Biz\VideoRecorder\Service\VideoRecorderService;
use support\Request;
use support\utils\ArrayToolkit;

class Device extends BaseController
{
    /**
     * @param Request $request
     * @api {GET} /sip/device/openLiveWithCameras 批量开启摄像头直播功能（不同云商操作不一样）
     */
    public function openLiveWithCameras(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            return json(['code' => self::ERROR_CODE_METHOD_FAILED, 'data' => null, 'message' => ''], 404);
        }
        $conditions = $request->get();
        $conditions['enabled'] = 0;
        $this->fillPartnerId($conditions);
        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $sort = $this->getSort($request);
        $options = $request->post();
        $result = $this->getCurrentLiveProviderStrategy()->activeAndOpenLiveWithCameras($conditions, $sort, $offset, $limit, $options);

        return $this->createSuccessJsonResponse($result);
    }

    /**
     *
     * @param Request $request
     * @api {GET} /sip/device/recorders 获取录像机列表
     */
    public function recorders(Request $request)
    {
        $conditions = $request->post();
        $this->fillPartnerId($conditions);
        $sort = $this->getSort($request);
        $total = $this->getCurrentLiveProviderStrategy()->countRecorders($conditions);
        $recorders = $this->getCurrentLiveProviderStrategy()->searchRecorders($conditions, $sort, 0, PHP_INT_MAX);
        $filter = new VideoRecorderFilter();
        $filter->filters($recorders);

        return $this->createSuccessJsonResponse([
            'total' => $total,
            'list' => $recorders
        ]);
    }

    /**
     *
     * @param Request $request
     * @api {GET} /sip/device/cameras 获取摄像头列表
     */
    public function cameras(Request $request)
    {
        $conditions = $request->post();
        $this->fillPartnerId($conditions);
        $conditions['enabled'] = 1;
        $sort = $this->getSort($request);
        $total = $this->getCurrentLiveProviderStrategy()->countVideoChannels($conditions);
        $videoChannels = $this->getCurrentLiveProviderStrategy()->searchCameras($conditions, $sort, 0, PHP_INT_MAX);
        $filter = new VideoChannelFilter();
        $filter->filters($videoChannels);

        return $this->createSuccessJsonResponse([
            'total' => $total,
            'list' => $videoChannels
        ]);
    }

    /**
     *
     * @param Request $request
     * @api {GET} /sip/device/trees  获取录像机-摄像头树
     */
    public function trees(Request $request)
    {
        $conditions = $request->post();
        $this->fillPartnerId($conditions);
        $deviceTrees = $this->getCurrentLiveProviderStrategy()->deviceTrees($conditions);

        return $this->createSuccessJsonResponse($deviceTrees);
    }

    /**
     *
     * @param Request $request
     * @api {GET} /sip/device/camera 获取单个摄像头信息
     */
    public function camera(Request $request)
    {
        $code = $request->post('code');
        $camera = $this->getCurrentLiveProviderStrategy()->getCamera($code);
        $filter = new VideoChannelFilter();
        $filter->filter($camera);

        return $this->createSuccessJsonResponse($camera);
    }

    /**
     *
     * @param Request $request
     * @api {GET} /sip/device/recorder 获取单个录像信息
     */
    public function recorder(Request $request)
    {
        $code = $request->post('code');
        $recorder = $this->getCurrentLiveProviderStrategy()->getVideoRecorder($code);
        $filter = new VideoRecorderFilter();
        $filter->filters($recorder);

        return $this->createSuccessJsonResponse($recorder);
    }

    /**
     * @param Request $request
     * @return \support\Response
     * @throws \Codeages\Biz\Framework\Service\Exception\NotFoundException
     * @api {POST} /sip/device/ptzControl 云台控制
     */
    public function ptzControl(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            return json(['code' => self::ERROR_CODE_METHOD_FAILED, 'data' => null, 'message' => 'Not Found'], 404);
        }

        $postData = $request->post();
        if (empty($postData['code'])) {
            return json(['code' => self::ERROR_CODE_METHOD_FAILED, 'data' => null, 'message' => 'Device Not Found'], 403);
        }

        $params = ArrayToolkit::parts($postData, ['ptzCommandType', 'speed']);
        list($code, $data, $msg) = $this->getCurrentLiveProviderStrategy()->devicePtzStart($postData['code'], $params);
        if ($code != self::BIS_SUCCESS_CODE) {
            return $this->createFailJsonResponse($msg);
        }

        return $this->createSuccessJsonResponse($data);
    }

    public function cover(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            return json(['code' => self::ERROR_CODE_METHOD_FAILED, 'data' => null, 'message' => 'Not Found'], 404);
        }

        $postData = $request->post();
        if (empty($postData['code'])) {
            return json(['code' => self::ERROR_CODE_METHOD_FAILED, 'data' => null, 'message' => 'Device Not Found'], 403);
        }

        $cover = $this->getCurrentLiveProviderStrategy()->getVideoCover($postData['code'], $postData['protocol'] ?? 'rtmp');

        if ($cover === null) {
            return $this->createFailJsonResponse("获取失败");
        }

        return $this->createSuccessJsonResponse(['base64_cover' => $cover]);
    }

    /**
     * @return VideoRecorderService
     */
    protected function getVideoRecorderService()
    {
        return $this->createService('VideoRecorder:VideoRecorderService');
    }

    /**
     * @return VideoChannelsService
     */
    protected function getVideoChannelsService()
    {
        return $this->createService('VideoChannels:VideoChannelsService');
    }
}