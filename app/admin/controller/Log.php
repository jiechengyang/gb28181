<?php

namespace app\admin\controller;

use app\admin\BaseController;
use app\admin\filters\SystemLogFilter;
use Biz\Constants;
use support\Request;
use support\utils\Paginator;
use Biz\SystemLog\Service\SystemLogService;
use support\utils\ArrayToolkit;

class Log extends BaseController
{
    public function systemLogModules(Request $request)
    {
        return $this->createSuccessJsonResponse(Constants::getSystemLogModules());
    }

    public function systemLogs(Request $request)
    {
        $conditions = [];
        $params = $request->get();
        if (!empty($params['keywords'])) {
            $conditions['keywordsLike'] = $params['keywords'];
        }

        if (!empty($params['module'])) {
            $conditions['module'] = $params['module'];
        }

        if (!empty($params['level'])) {
            $conditions['level'] = $params['level'];
        }

        if (!empty($params['action'])) {
            $conditions['action'] = $params['action'];
        }

        if (!empty($params['startTime'])) {
            $conditions['startDateTime_GE'] = strtotime(trim($params['startTime'], '"'));
        }

        if (!empty($params['endTime'])) {
            $conditions['startDateTime_LE'] = strtotime(trim($params['endTime'], '"'));
        }

        $total = $this->getSystemLogService()->countLogs($conditions);
        list($offset, $limit) = $this->getOffsetAndLimit($request);
        $sort = $this->getSort($request);
        $sort['id'] = 'DESC';
        $paginator = new Paginator($offset, $total, $request->uri(), $limit);
        $logs = $this->getSystemLogService()->searchLogs($conditions, $sort, $paginator->getOffsetCount(), $paginator->getPerPageCount());
        $filter = new SystemLogFilter();
        $filter->filters($logs);
        $userIds = ArrayToolkit::column($logs, 'userId');
        $users = $this->getUserService()->findUsersByIds($userIds);
        foreach ($logs as &$log) {
            $log['username'] = $users[$log['userId']]['nickname'] ?? '---';
        }
        
        return $this->createSuccessJsonResponse([
            'logs' => $logs,
            'paginator' => Paginator::toArray($paginator)
        ]);
    }

    /**
     * 
     *
     * @return SystemLogService
     */
    protected function getSystemLogService()
    {
        return $this->createService('SystemLog:SystemLogService');
    }
}