<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\filters\DeviceActiveLogFilter;
use app\admin\filters\DeviceRegisterLogFilter;
use app\admin\filters\VideoRecorderFilter;
use app\admin\filters\VideoChannelFilter;
use Biz\Constants;
use Biz\DataFilters\Filter;
use Biz\DeviceActiveLog\Service\DeviceActiveLogService;
use Biz\Record\Service\RecordService;
use Biz\ThirdParty\Service\ThirdPartyService;
use Codeages\Biz\Framework\Service\Exception\NotFoundException;
use support\Request;
use support\utils\Paginator;
use support\utils\ArrayToolkit;
use Biz\VideoChannels\Service\VideoChannelsService;
use Biz\VideoRecorder\Service\VideoRecorderService;
use Biz\DeviceRegisterLog\Service\DeviceRegisterLogService;
use support\exception\BadRequestHttpException;

class Device extends BaseController
{
    public function liveUrl(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        $mainId = $request->post('main_id');
        if (empty($mainId)) {
            return $this->createFailJsonResponse();
        }

        $remoteIp = $request->getRealIp();
        $intranet = \is_local_client($remoteIp, ['127.0.0.1', '192.168.*.*']);
        // TODO 针对内网不能访问公网端口做特殊处理，此时内网未做端口回流
        $localClientIps = config('app.ak_config.local_ips');
        if (!empty($localClientIps) && \is_local_client($remoteIp, explode('|', $localClientIps))) {
            $intranet = false;
        }

        $playUrls = $this->getVideoChannelsService()->liveUrl($mainId, \is_https_request(), $intranet);

        return $this->createSuccessJsonResponse([
            'playUrls' => $playUrls
        ]);
    }

    public function ptzCtrl(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        $mainId = $request->post('main_id');
        $commandType = intval($request->post('command_type'));
        if (empty($mainId)) {
            return $this->createFailJsonResponse();
        }

        $result = $this->getVideoChannelsService()->ptzCtrl($mainId, $commandType);

        if ($result) {
            return $this->createSuccessJsonResponse();
        }

        return $this->createFailJsonResponse();
    }

    public function videoCover(Request $request)
    {
        $mediaServerId = $request->get('mediaServerId', '');
        $url = $request->get('url', '');
        if (empty($url) || empty($mediaServerId)) {
            return $this->returnDefaultCover($request);
        }

        $cover = $this->getVideoChannelsService()->getVideoCover($mediaServerId, $url);
        // 这里有问题
        if (empty($cover) || !is_string($cover)) {
            return $this->returnDefaultCover($request);
        }

        return $this->createSuccessJsonResponse(['cover' => sprintf("data:image/jpeg;base64,%s", $cover)]);
    }

    public function videoOnlineList(Request $request)
    {
        $conditions = [];
        // TODO: 这里如果ak拿到的在线的推流摄像头和zlmedia正在推流的rtp端口数量不一致，则需要调用zlm接口对齐
        if (!empty($request->get('mainId'))) {
            $conditions['mainId'] = $request->get('mainId');
        }
        $remoteIp = $request->getRealIp();
        $videos = $this->getVideoChannelsService()->getVideoOnlineList($conditions, \is_local_client($remoteIp, ['127.0.0.1', '192.168.*.*']));

        return $this->createSuccessJsonResponse($videos);
    }

    public function batchUpdateRtpProto(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }
        $ids = $request->post('ids');
        if (empty($ids)) {
            return $this->createFailJsonResponse('设置失败，未有可设置的设备');
        }

        $rtpRroto = $request->post('rtp_proto');
        if (empty($rtpRroto)) {
            return $this->createFailJsonResponse('设置失败，推流方式未选择');
        }

        $this->getVideoChannelsService()->batchUpdateRtpProto($ids, $rtpRroto);

        return $this->createSuccessJsonResponse();
    }

    public function batchBindRecorder(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }
        $ids = $request->post('ids');
        if (empty($ids)) {
            return $this->createFailJsonResponse('设置失败，未有可设置的设备');
        }

        $recorderId = $request->post('recorderId');
        if (empty($recorderId)) {
            return $this->createFailJsonResponse('设置失败，录像机必须选择');
        }

        $this->getVideoChannelsService()->batchBindRecorder($ids, $recorderId);

        return $this->createSuccessJsonResponse(['async' => true]);
    }

    public function batchBindRecordPlan(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }
        $ids = $request->post('ids');
        if (empty($ids)) {
            return $this->createFailJsonResponse('设置失败，未有可设置的设备');
        }

        $planId = $request->post('planId', 0);

        $this->getVideoChannelsService()->batchBindRecordPlan($ids, $planId);

        return $this->createSuccessJsonResponse(['async' => true]);
    }

    public function nvrItems(Request $request)
    {
        $conditions = [];
        if ($request->get('parter_id')) {
            $conditions['parterId'] = $request->get('parter_id');
        }

        $items = $this->getVideoRecorderService()->searchRecorders($conditions, ['createdTime' => 'DESC'], 0, PHP_INT_MAX, ['id', 'device_name', 'device_id']);

        return $this->createSuccessJsonResponse($items);
    }

    public function batchUpdateMediaServer(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }
        $ids = $request->post('ids');
        if (empty($ids)) {
            return $this->createFailJsonResponse('设置失败，未有可同步的设备');
        }

        $mediaServerId = $request->post('mediaServerId');
        if (empty($mediaServerId)) {
            return $this->createFailJsonResponse('设置失败，流媒体服务节点必须提供');
        }

        $this->getVideoChannelsService()->batchUpdateMediaServer($ids, $mediaServerId);

        return $this->createSuccessJsonResponse(['async' => true]);
    }

    public function mediaServerList(Request $request)
    {
        $mediaServerList = $this->getVideoChannelsService()->getMediaServerList();

        return $this->createSuccessJsonResponse($mediaServerList);
    }

    public function batchSyncIpc(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }
        $ids = $request->post('ids');
        if (empty($ids)) {
            return $this->createFailJsonResponse('同步失败，未有可同步的设备');
        }

        is_string($ids) && $ids = explode(',', $ids);

        $this->getVideoChannelsService()->batchSyncDevicesControl($ids);

        return $this->createSuccessJsonResponse([
            'async' => true
        ]);
    }

    public function editNvr(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        $id = $request->get('id');
        if (empty($id)) {
            throw new BadRequestHttpException("请求参数错误", null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        $nvr = $this->getVideoRecorderService()->getVideoRecorderById($id);
        if (empty($nvr)) {
            throw new BadRequestHttpException("设备不存在", null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        $scenario = $request->post('scenario', 'update');
        $formData = $request->post();
        if ('changeDeviceName' === $scenario) {
            $formData = $this->checkRequiredFields(['device_name'], $formData);
            $res = $this->getVideoRecorderService()->updateVideoRecorder($id, [
                'device_name' => $formData['device_name']
            ]);
            if ($res) {
                return $this->createSuccessJsonResponse();
            }

            return $this->createFailJsonResponse('名称更新失败');
        }

        return $this->createSuccessJsonResponse();
    }

    public function editIpc(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        $id = $request->get('id');
        if (empty($id)) {
            throw new BadRequestHttpException("请求参数错误", null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        $ipc = $this->getVideoChannelsService()->getVideoChannelById($id);
        if (empty($ipc)) {
            throw new BadRequestHttpException("设备不存在", null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        $scenario = $request->post('scenario', 'update');
        $formData = $request->post();
        if ('changeChannelName' === $scenario) {
            $formData = $this->checkRequiredFields(['channel_name'], $formData);
            $res = $this->getVideoChannelsService()->changeChannelName($id, $formData['channel_name']);
            if ($res) {
                return $this->createSuccessJsonResponse();
            }

            return $this->createFailJsonResponse('通道名称更新失败');
        }

        return $this->createSuccessJsonResponse();
    }

    public function batchLockIpc(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        $ids = $request->post('ids');
        if (empty($ids)) {
            return $this->createFailJsonResponse('封禁失败，未提供封禁数据');
        }

        is_string($ids) && $ids = explode(',', $ids);
        $res = $this->getVideoChannelsService()->batchLock($ids);
        if ($res) {
            return $this->createSuccessJsonResponse(null, '封禁成功');
        }

        return $this->createFailJsonResponse('封禁失败了');
    }

    public function batchDeleteIpc(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        $ids = $request->post('ids');
        if (empty($ids)) {
            return $this->createFailJsonResponse('删除失败，未提供删除数据');
        }
        is_string($ids) && $ids = explode(',', $ids);

        $res = $this->getVideoChannelsService()->batchDelete($ids);
        if ($res) {
            return $this->createSuccessJsonResponse(null, '删除成功');
        }

        return $this->createFailJsonResponse('删除失败了');
    }

    public function batchActiveIpc(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        $ids = $request->post('ids');
        if (empty($ids)) {
            return $this->createFailJsonResponse('激活失败，未提供设备');
        }

        is_string($ids) && $ids = explode(',', $ids);
        $this->getVideoChannelsService()->batchActiveDevices($ids);
        return $this->createSuccessJsonResponse([
            'async' => true
        ]);
    }

    public function batchOpenLiveIpc(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        $ids = $request->post('ids');
        if (empty($ids)) {
            return $this->createFailJsonResponse('开启失败，未提供设备');
        }

        is_string($ids) && $ids = explode(',', $ids);
        $this->getVideoChannelsService()->batchOpenLive($ids);
        return $this->createSuccessJsonResponse([
            'async' => true
        ]);
    }

    public function batchCloseLiveIpc(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        $ids = $request->post('ids');
        if (empty($ids)) {
            return $this->createFailJsonResponse('关闭失败，未提供设备');
        }
        is_string($ids) && $ids = explode(',', $ids);
        $this->getVideoChannelsService()->batchCloseLive($ids);

        return $this->createSuccessJsonResponse([
            'async' => true
        ]);
    }

    public function statusItems(Request $request)
    {
        return $this->createSuccessJsonResponse(Constants::getDeviceStatusItems());
    }

    public function saveBindPartner(Request $request)
    {
        $formData = $request->post();
        $formData = $this->checkRequiredFields(['target_type', 'parter_id', 'ids'], $formData);
        $thirdPartner = $this->getThirdPartyService()->getThirdParty($formData['parter_id']);
        if (empty($thirdPartner)) {
            return $this->createFailJsonResponse('合作方不存在');
        }

        if (empty($formData['ids'])) {
            return $this->createFailJsonResponse('未提供设备id');
        }

        $ids = is_string($formData['ids']) ? explode(',', $formData['ids']) : $formData['ids'];
        if ('nvr' === $formData['target_type']) {
            $recorders = $this->getVideoRecorderService()->searchRecorders(['ids' => $ids], [], 0, PHP_INT_MAX, ['id']);
            $ids = ArrayToolkit::column($recorders, 'id');
            if (empty($ids)) {
                return $this->createFailJsonResponse('提供的录像机设备id不存在');
            }

            $result = $this->getVideoRecorderService()->batchUpdateParterId([
                'ids' => $ids
            ], $thirdPartner['id']);

            $this->getLogService()->info('device', 'bind_partner', '录像机绑定合作方', [
                'finishedCount' => $result,
                'thirdPartner' => $thirdPartner,
                'currentIp' => $request->getRealIp()
            ]);

            return $this->createSuccessJsonResponse(['finishedCount' => $result]);
        } elseif ('ipc' === $formData['target_type']) {
            $videos = $this->getVideoChannelsService()->searchVideoChannels(['ids' => $ids], [], 0, PHP_INT_MAX, ['id']);
            $ids = ArrayToolkit::column($videos, 'id');
            if (empty($ids)) {
                return $this->createFailJsonResponse('提供的摄像头设备id不存在');
            }

            $result = $this->getVideoChannelsService()->batchUpdatePartnerId([
                'ids' => $ids
            ], $thirdPartner['id']);
            $this->getLogService()->info('device', 'bind_partner', '摄像头绑定合作方', [
                'finishedCount' => $result,
                'thirdPartner' => $thirdPartner,
                'currentIp' => $request->getRealIp()
            ]);

            return $this->createSuccessJsonResponse(['finishedCount' => $result]);
        }

        return $this->createFailJsonResponse('目标设备类型不存在');
    }

    public function unboundPartnerDevices(Request $request)
    {
        $type = $request->get('type', 'nvr');
        $method = sprintf('searchUnbound%sDevices', ucfirst($type));
        if (!method_exists($this, $method)) {
            throw new BadRequestHttpException("请求参数type错误或缺少", null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        return $this->{$method}($request);
    }

    public function nvrShow(Request $request)
    {
        $id = $request->get('id');
        if (empty($id)) {
            throw  new NotFoundException("访问不存在");
        }

        $nvr = $this->getVideoRecorderService()->getVideoRecorderById($id);
        if (empty($nvr)) {
            throw new BadRequestHttpException("请求录像机ID错误", null, self::ERROR_CODE_GET_DATA_FAILED);
        }

        $filter = new VideoRecorderFilter();
        $filter->setMode(VideoRecorderFilter::PUBLIC_MODE);
        $filter->filter($nvr);
        $thirdParty = $this->getThirdPartyService()->getThirdParty($nvr['parter_id']);
        $nvr['partner_name'] = $thirdParty['partner_name'] ?? '---';

        return $this->createSuccessJsonResponse($nvr);
    }

    public function nvrDevices(Request $request)
    {
        $conditions = ['noParterId' => 0];
        $params = $request->get();
        if (!empty($params['parterId'])) {
            $conditions['parterId'] = $params['parterId'];
        }

        if (!empty($params['status'])) {
            $conditions['status'] = $params['status'];
        }

        if (!empty($params['[keywords'])) {
            $conditions['keywordsLike'] = $params['keywords'];
        }

        $total = $this->getVideoRecorderService()->countRecorders($conditions);
        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $sort = $this->getSort($request);
        $sort['createdTime'] = 'DESC';
        $sort['lastOnlineTime'] = 'DESC';
        $paginator = new Paginator($offset, $total, $request->uri(), $limit);
        $recorders = $this->getVideoRecorderService()->searchRecorders($conditions, $sort, $paginator->getOffsetCount(), $paginator->getPerPageCount());
        $filter = new VideoRecorderFilter();
        $filter->filters($recorders);
        $parterIds = ArrayToolkit::column($recorders, 'parter_id');
        empty($parterIds) && $parterIds = [-1];
        $parters = $this->getThirdPartyService()->findThirdPartiesByIds($parterIds);
        foreach ($recorders as &$recorder) {
            $recorder['partner_title'] = $parters[$recorder['parter_id']]['partner_name'] ?? '---';
        }

        return $this->createSuccessJsonResponse([
            'devices' => $recorders,
            'paginator' => Paginator::toArray($paginator)
        ]);
    }

    public function ipcDevices(Request $request)
    {
        $conditions = ['noParterId' => 0, 'locked' => 0];
        $params = $request->get();
        if (!empty($params['parterId'])) {
            $conditions['parterId'] = $params['parterId'];
        }

        if (!empty($params['recorderId'])) {
            $conditions['recorderId'] = $params['recorderId'];
        }

        if (isset($params['status']) && $params['status'] !== '') {
            $conditions['deviceStatus'] = $params['status'];
        }

        if (isset($params['liveType']) && $params['liveType'] !== '') {
            $conditions['autoLive'] = 2 == $params['liveType'] ? 1 : 0;
        }

        if (isset($params['enabled']) && $params['enabled'] !== '') {
            $conditions['enabled'] = $params['enabled'];
        }

        if (!empty($params['keywords'])) {
            $conditions['keywordsLike'] = $params['keywords'];
        }

        $total = $this->getVideoChannelsService()->countVideoChannels($conditions);
        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $sort = $this->getSort($request);
        $sort['createdTime'] = 'DESC';
        $sort['enabled'] = 'DESC';
        $sort['lastOnlineTime'] = 'DESC';
        $paginator = new Paginator($offset, $total, $request->uri(), $limit);
        $channels = $this->getVideoChannelsService()->searchVideoChannels($conditions, $sort, $paginator->getOffsetCount(), $paginator->getPerPageCount());
        $filter = new VideoChannelFilter();
        $filter->filters($channels);
        $recordIds = ArrayToolkit::column($channels, 'recorder_id');
        $thirdPartyIds = ArrayToolkit::column($channels, 'parter_id');
        $planIds = ArrayToolkit::column($channels, 'record_plan_id');
        $recorders = ArrayToolkit::index($this->getVideoRecorderService()->searchRecorders(['ids' => $recordIds], [], 0, count($recordIds), ['id', 'device_name', 'device_id']), 'id');
        $thirdParties = ArrayToolkit::index($this->getThirdPartyService()->searchThirdParties(['ids' => $thirdPartyIds], [], 0, count($thirdPartyIds), ['id', 'partner_name']), 'id');
        $plans = ArrayToolkit::index($this->getRecordService()->searchRecordPlans(['ids' => $planIds], [], 0, count($planIds), ['id', 'name']), 'id');
        foreach ($channels as &$channel) {
            $recorderId = $channel['recorder_id'];
            if (isset($recorders[$recorderId])) {
                $recorder = $recorders[$recorderId];
                $channel['recorder_name'] = $recorder['device_name'] ?: $recorder['device_id'];
            } else {
                $channel['recorder_name'] = '---';
            }

            $thirdPartyId = $channel['parter_id'];
            if (isset($thirdParties[$thirdPartyId])) {
                $thirdParty = $thirdParties[$thirdPartyId];
                $channel['third_party_name'] = $thirdParty['partner_name'];
            } else {
                $channel['third_party_name'] = '---';
            }

            if (isset($plans[$channel['record_plan_id']])) {
                $channel['record_plan_name'] = $plans[$channel['record_plan_id']]['name'];
            } else {
                $channel['record_plan_name'] = '---';
            }
        }

        return $this->createSuccessJsonResponse([
            'devices' => $channels,
            'paginator' => Paginator::toArray($paginator)
        ]);
    }

    public function ipcShow(Request $request)
    {
        $id = $request->get('id');
        if (empty($id)) {
            throw  new NotFoundException("访问不存在");
        }

        $ipc = $this->getVideoChannelsService()->getVideoChannelById($id);
        if (empty($ipc)) {
            throw new BadRequestHttpException("请求摄像头ID错误", null, self::ERROR_CODE_GET_DATA_FAILED);
        }
        $filter = new VideoChannelFilter();
        $filter->setMode(VideoChannelFilter::PUBLIC_MODE);
        $filter->filter($ipc);

        return $this->createSuccessJsonResponse($ipc);
    }

    public function registerLogs(Request $request)
    {
        $conditions = [];
        $params = $request->get();
        if (!empty($params['deviceId'])) {
            $conditions['deviceId'] = $params['deviceId'];
        }

        if (!empty($params['nvrId'])) {
            $nvr = $this->getVideoRecorderService()->getVideoRecorderById($params['nvrId']);
            if (!empty($nvr)) {
                $conditions['deviceId'] = $nvr['device_id'];
            }
        }

        if (!empty($params['keywords'])) {
            $conditions['deviceIdLike'] = $params['keywords'];
        }

        $total = $this->getDeviceRegisterService()->countLogs($conditions);
        list($offset, $limit) = $this->getOffsetAndLimit($request, 'pageNo', 'pageSize');
        $sort = $this->getSort($request);
        $sort['createdTime'] = 'DESC';
        $paginator = new Paginator($offset, $total, $request->uri(), $limit);
        $logs = $this->getDeviceRegisterService()->searchLogs($conditions, $sort, $paginator->getOffsetCount(), $paginator->getPerPageCount());
        $filter = new DeviceRegisterLogFilter();
        $filter->filters($logs);

        return $this->createSuccessJsonResponse([
            'data' => $logs,
            'pageSize' => $paginator->getPerPageCount(),
            'pageNo' => $paginator->getCurrentPage(),
            'totalPage' => $paginator->getTotalPage(),
            'totalCount' => $total
        ]);
    }

    public function activeLogs(Request $request)
    {
        $conditions = [];
        $params = $request->get();
        if (!empty($params['deviceId'])) {
            $conditions['deviceId'] = $params['deviceId'];
        }

        if (!empty($params['nvrId'])) {
            $nvr = $this->getVideoRecorderService()->getVideoRecorderById($params['nvrId']);
            if (!empty($nvr)) {
                $conditions['deviceId'] = $nvr['device_id'];
            }
        }

        if (!empty($params['type'])) {
            $conditions['type'] = $params['type'];
        }

        if (!empty($params['keywords'])) {
            $conditions['deviceIdLike'] = $params['keywords'];
        }

        $total = $this->getDeviceActiveLogService()->countLogs($conditions);
        list($offset, $limit) = $this->getOffsetAndLimit($request, 'pageNo', 'pageSize');
        $sort = $this->getSort($request);
        $sort['createdTime'] = 'DESC';
        $paginator = new Paginator($offset, $total, $request->uri(), $limit);
        $logs = $this->getDeviceActiveLogService()->searchLogs($conditions, $sort, $paginator->getOffsetCount(), $paginator->getPerPageCount());
        $filter = new DeviceActiveLogFilter();
        $filter->filters($logs);

        return $this->createSuccessJsonResponse([
            'data' => $logs,
            'pageSize' => $paginator->getItemCount(),
            'pageNo' => $paginator->getCurrentPage(),
            'totalPage' => $paginator->getItemCount(),
            'totalCount' => $total
        ]);
    }

    protected function searchUnboundNvrDevices(Request $request)
    {
        $conditions = ['parterId' => 0];
        $params = $request->get();
//        $conditions = array_merge($params, $conditions);
        $total = $this->getVideoRecorderService()->countRecorders($conditions);
        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $sort = $this->getSort($request);
        $sort['createdTime'] = 'DESC';
        $sort['lastOnlineTime'] = 'DESC';
        $paginator = new Paginator($offset, $total, $request->uri(), $limit);
        $recorders = $this->getVideoRecorderService()->searchRecorders($conditions, $sort, $paginator->getOffsetCount(), $paginator->getPerPageCount());
        $filter = new VideoRecorderFilter();
        $filter->filters($recorders);

        return $this->createSuccessJsonResponse([
            'devices' => $recorders,
            'paginator' => Paginator::toArray($paginator)
        ]);
    }

    protected function searchUnboundIpcDevices(Request $request)
    {
        $conditions = ['parterId' => 0];
        $params = $request->get();
        $conditions = array_merge($params, $conditions);
        $total = $this->getVideoChannelsService()->countVideoChannels($conditions);
        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $sort = $this->getSort($request);
        $sort['createdTime'] = 'DESC';
        $sort['lastOnlineTime'] = 'DESC';
        $paginator = new Paginator($offset, $total, $request->uri(), $limit);
        $recorders = $this->getVideoChannelsService()->searchVideoChannels($conditions, $sort, $paginator->getOffsetCount(), $paginator->getPerPageCount());
        $filter = new VideoChannelFilter();
        $filter->filters($recorders);

        return $this->createSuccessJsonResponse([
            'devices' => $recorders,
            'paginator' => Paginator::toArray($paginator)
        ]);
    }

    protected function returnDefaultCover(Request $request)
    {
        $defaultFile = $request->uri() . '/assets/images/default/none-cover.png';

        return $this->createSuccessJsonResponse(['cover' => sprintf("data:image/png;base64,/%s", base64_encode($defaultFile))]);
    }


    /**
     * @return VideoChannelsService
     * @throws \Webman\Exception\NotFoundException
     */
    protected function getVideoChannelsService()
    {
        return $this->getBiz()->service('VideoChannels:VideoChannelsService');
    }

    /**
     * @return VideoRecorderService
     * @throws \Webman\Exception\NotFoundException
     */
    protected function getVideoRecorderService()
    {
        return $this->getBiz()->service('VideoRecorder:VideoRecorderService');
    }

    /**
     *
     * @return ThirdPartyService
     */
    protected function getThirdPartyService()
    {
        return $this->createService('ThirdParty:ThirdPartyService');
    }

    /**
     *
     * @return DeviceRegisterLogService
     */
    protected function getDeviceRegisterService()
    {
        return $this->createService('DeviceRegisterLog:DeviceRegisterLogService');
    }

    /**
     *
     * @return DeviceActiveLogService
     */
    protected function getDeviceActiveLogService()
    {
        return $this->createService('DeviceActiveLog:DeviceActiveLogService');
    }

    /**
     * @return RecordService
     */
    protected function getRecordService()
    {
        return $this->createService('Record:RecordService');
    }
}