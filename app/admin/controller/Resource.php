<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\filters\PlayRecordFilter;
use support\Request;
use support\utils\Paginator;
use Biz\PlayRecord\Service\PlayRecordService;

class Resource extends BaseController
{
    public function playRecords(Request $request)
    {
        $conditions = [];
        $params = $request->get();
        if (!empty($params['keywords'])) {
            $conditions['keywordsLike'] = $params['keywords'];
        }

        if (!empty($params['startTime'])) {
            $conditions['startTime'] = strtotime(trim($params['startTime'], '"'));
        }

        if (!empty($params['endTime'])) {
            $conditions['endTime'] = strtotime(trim($params['endTime'], '"'));
        }

        $total = $this->getPlayRecordService()->countRecords($conditions);
        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $sort = $this->getSort($request);
        $sort['createdTime'] = 'DESC';
        $paginator = new Paginator($offset, $total, $request->uri(), $limit);
        $records = $this->getPlayRecordService()->searchRecords($conditions, $sort, $paginator->getOffsetCount(), $paginator->getPerPageCount());
        $filter = new PlayRecordFilter();
        $filter->filters($records);
        return $this->createSuccessJsonResponse([
            'records' => $records,
            'paginator' => Paginator::toArray($paginator)
        ]);
    }

    /**
     * 
     *
     * @return PlayRecordService
     */
    protected function getPlayRecordService()
    {
        return $this->createService('PlayRecord:PlayRecordService');
    }
}