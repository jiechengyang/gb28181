<?php


namespace app\admin\controller;


use app\admin\BaseController;
use app\admin\filters\RecordPlanFilter;
use app\admin\filters\RecordVideoChannelFilter;
use Biz\DataFilters\Filter;
use Biz\DataFilters\Resources\RecordFileFilter;
use Biz\Record\Service\RecordService;
use Biz\VideoChannels\Service\VideoChannelsService;
use support\exception\BadRequestHttpException;
use support\Request;
use support\utils\ArrayToolkit;
use support\utils\Paginator;

class Record extends BaseController
{
    public function summary(Request $request)
    {
        $params = $request->get();
        $conditions = [];
        if (!empty($params['plan_id'])) {
            $conditions['planId'] = $params['plan_id'];
        }

        if (!empty($params['main_id'])) {
            $conditions['mainId'] = $params['main_id'];
        }

        if (!empty($params['record_date'])) {
            $conditions['recordDate'] = $params['record_date'];
        }

        $items = $this->getRecordService()->summaryDateFileSizeAndHourList($conditions);

        return $this->createSuccessJsonResponse($items);
    }

    public function files(Request $request)
    {
        $conditions = ['deletedTime' => 0, 'duration_GT' => 0];
        $params = $request->get();
        if (empty($params['record_date'])) {
            $params['record_date'] = date('Y-m-d');
        }
        $conditions['recordDate'] = $params['record_date'];
        if (!empty($params['main_id'])) {
            $conditions['mainId'] = $params['main_id'];
        }

        $files = $this->getRecordService()->searchFiles($conditions, ['created_time' => 'ASC'], 0, PHP_INT_MAX, [
            'id',
            'record_date',
            'start_time',
            'end_time',
            'duration',
            'download_url'
        ]);
        $filter = new RecordFileFilter();
        $filter->filters($files);

        return $this->createSuccessJsonResponse($files);
    }

    public function recordChannels(Request $request)
    {
        $params = $request->get();
        $conditions = ['noRecordPlanId' => 0];
        if (!empty($params['keywords'])) {
            $conditions['keywordsLike'] = $params['keywords'];
        }
        if (isset($params['record_status']) && is_numeric($params['record_status'])) {
            $conditions['recordStatus'] = $params['record_status'];
        }

        $total = $this->getVideoChannelsService()->countVideoChannels($conditions);
        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $sort = $this->getSort($request);
        $sort['createdTime'] = 'DESC';
        $sort['enabled'] = 'DESC';
        $sort['lastOnlineTime'] = 'DESC';
        $paginator = new Paginator($offset, $total, $request->uri(), $limit);
        $channels = $this->getVideoChannelsService()->searchVideoChannels($conditions, $sort, $paginator->getOffsetCount(), $paginator->getPerPageCount());
        $filter = new RecordVideoChannelFilter();
        $filter->filters($channels);
        $planIds = ArrayToolkit::column($channels, 'record_plan_id');
        $plans = ArrayToolkit::index($this->getRecordService()->searchRecordPlans(['ids' => $planIds], [], 0, count($planIds), ['id', 'name']), 'id');
        foreach ($channels as &$channel) {
            if (isset($plans[$channel['record_plan_id']])) {
                $channel['record_plan_name'] = $plans[$channel['record_plan_id']]['name'];
            } else {
                $channel['record_plan_name'] = '---';
            }
        }

        return $this->createSuccessJsonResponse([
            'items' => $channels,
            'paginator' => Paginator::toArray($paginator)
        ]);
    }



    public function planItems(Request $request)
    {
        $conditions = ['status' => 1];
        if (!empty($request->get('keywords'))) {
            $conditions['nameLike'] = $request->get('keywords');
        }

        $items = $this->getRecordService()->searchRecordPlans($conditions, ['id' => 'DESC'], 0, PHP_INT_MAX, ['id', 'name']);

        return $this->createSuccessJsonResponse($items);
    }

    public function plans(Request $request)
    {
        $conditions = [];
        if (!empty($request->get('keywords'))) {
            $conditions['nameLike'] = $request->get('keywords');
        }

        if (is_numeric($request->get('status', null))) {
            $conditions['status'] = $request->get('status');
        }

        $total = $this->getRecordService()->countRecordPlans($conditions);
        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $sort = $this->getSort($request);
        $sort['created_time'] = 'DESC';
        $paginator = new Paginator($offset, $total, $request->uri(), $limit);
        $items = $this->getRecordService()->searchRecordPlans($conditions, $sort, $paginator->getOffsetCount(), $paginator->getPerPageCount());
        $filter = new RecordPlanFilter();
        $filter->filters($items);

        return $this->createSuccessJsonResponse([
            'items' => $items,
            'paginator' => Paginator::toArray($paginator)
        ]);
    }

    public function plan(Request $request)
    {
        $id = $request->input('id');
        if (empty($id)) {
            return $this->createFailJsonResponse('Not Found Record Plan', 404);
        }

        $plan = $this->getRecordService()->getRecordPlanById($id);
        if (empty($plan)) {
            return $this->createFailJsonResponse('Not Found Record Plan', 404);
        }
        $filter = new RecordPlanFilter();
        $filter->filter($plan);

        return $this->createSuccessJsonResponse([
            'plan' => $plan
        ]);
    }

    public function addPlan(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }
        $fields = $request->post();
        $fields['partner_id'] = $this->getCurrentUser()->offsetGet('third_party_id');
        $recordPlan = $this->getRecordService()->createRecordPlan($fields);
        $filter = new RecordPlanFilter();
        $filter->filter($recordPlan);

        return $this->createSuccessJsonResponse([
            'plan' => $recordPlan
        ]);
    }

    public function editPlan(Request $request)
    {
        if ('POST' !== strtoupper($request->method())) {
            throw new BadRequestHttpException('访问出错', null, self::ERROR_CODE_GET_DATA_FAILED);
        }
        $id = $request->input('id');
        if (empty($id)) {
            return $this->createFailJsonResponse('Not Found Record Plan', 404);
        }
        $fields = $request->post();
        $fields['partner_id'] = $this->getCurrentUser()->offsetGet('third_party_id');
        $recordPlan = $this->getRecordService()->updateRecordPlan($id, $fields);
        $filter = new RecordPlanFilter();
        $filter->filter($recordPlan);

        return $this->createSuccessJsonResponse([
            'plan' => $recordPlan
        ]);
    }

    public function delPlan(Request $request)
    {
        $id = $request->input('id');
        if (empty($id)) {
            return $this->createFailJsonResponse('Not Found Record Plan', 404);
        }
        if ($this->getRecordService()->deleteRecordPlanById($id)) {
            return $this->createSuccessJsonResponse();
        }

        return $this->createFailJsonResponse();
    }

    public function delPlanRange(Request $request)
    {
        $id = $request->get('id');
        if ($this->getRecordService()->deleteRecordPlanRangeById($id)) {
            return $this->createSuccessJsonResponse();
        }

        return $this->createFailJsonResponse();
    }

    /**
     * 清空摄像头录像回放
     *
     * @param Request $request
     */
    public function delPlayBack(Request $request)
    {
        $id = $request->input('video_id');
        if (empty($id)) {
            return $this->createFailJsonResponse('Not Found Record Video', 404);
        }

        $this->getRecordService()->delPlayBack($id);

        return $this->createSuccessJsonResponse();
    }

    /**
     * 关闭摄像头录像
     *
     * @param Request $request
     */
    public function closePlayBack(Request $request)
    {
        $id = $request->input('video_id');
        if (empty($id)) {
            return $this->createFailJsonResponse('Not Found Record Video', 404);
        }

        $this->getRecordService()->closePlayBack($id);

        return $this->createSuccessJsonResponse();
    }

    /**
     * 开启摄像头录像
     *
     * @param Request $request
     */
    public function startPlayBack(Request $request)
    {
        $id = $request->input('video_id');
        if (empty($id)) {
            return $this->createFailJsonResponse('Not Found Record Video', 404);
        }

        $this->getRecordService()->startPlayBack($id);

        return $this->createSuccessJsonResponse();
    }

    /**
     * @return RecordService
     */
    protected function getRecordService()
    {
        return $this->createService('Record:RecordService');
    }

    /**
     * @return VideoChannelsService
     */
    protected function getVideoChannelsService()
    {
        return $this->createService('VideoChannels:VideoChannelsService');
    }
}