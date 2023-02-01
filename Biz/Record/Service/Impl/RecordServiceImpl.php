<?php

namespace Biz\Record\Service\Impl;

use Biz\BaseService;

use Biz\Constants;
use Biz\Record\Dao\RecordFileDao;
use Biz\Record\Dao\RecordPlanRangeDao;
use Biz\Record\Exception\RecordException;
use Biz\Record\Service\RecordService;
use Biz\Record\Dao\RecordPlanDao;
use Biz\SystemLog\Service\SystemLogService;
use Biz\VideoChannels\Service\VideoChannelsService;
use Illuminate\Support\Arr;
use support\utils\ArrayToolkit;

class RecordServiceImpl extends BaseService implements RecordService
{
    const DEFAULT_RECORD_PLAN_LIMIT_DAY = 7;

    /**
     * 根据条件计算录制文件大小
     *
     * @param array $conditions
     * @return mixed
     */
    public function getRecordFileSize(array $conditions = [])
    {
        return $this->getRecordFileDao()->sumFileSize($conditions);
    }

    /**
     * 获取当前录制模板录制的天数列表
     *
     * @param array $conditions
     * @param string $sort
     * @return mixed[]
     */
    public function getRecordFileDateList(array $conditions = [], $sort = 'DESC')
    {
        return $this->getRecordFileDao()->getRecordFileDateList($conditions, $sort);
    }

    public function getRecordFile($id)
    {
        return $this->getRecordFileDao()->get($id);
    }

    /**
     * @param $videoId
     * @return mixed
     */
    public function delPlayBack($videoId)
    {
        $video = $this->getVideoChannelsService()->getVideoChannelById($videoId);
        if (empty($video)) {
            throw RecordException::CLEAR_VIDEO_RECORD_ERROR_NOT_FOUND_VIDEO();
        }

        $this->batchDeleteFiles(['mainId' => $video['main_id']]);
    }

    /**
     * 关闭摄像头录像
     *
     * @param $videoId
     * @return mixed
     */
    public function closePlayBack($videoId)
    {
        $video = $this->getVideoChannelsService()->getVideoChannelById($videoId);
        if (empty($video)) {
            throw RecordException::CLEAR_VIDEO_RECORD_ERROR_NOT_FOUND_VIDEO();
        }

//        $this->getVideoChannelsService()->stopRecord($video);// 立即暂停
        // 不是立即暂停
        $this->getVideoChannelsService()->updateRecordStatusById($videoId, Constants::VIDEO_CHANNEL_RECORD_STATUS_CLOSE);
    }

    /**
     * 开启摄像头录像
     *
     * @param $videoId
     * @return mixed
     */
    public function startPlayBack($videoId)
    {
        $video = $this->getVideoChannelsService()->getVideoChannelById($videoId);
        if (empty($video)) {
            throw RecordException::CLEAR_VIDEO_RECORD_ERROR_NOT_FOUND_VIDEO();
        }

        if ($video['record_status'] === Constants::VIDEO_CHANNEL_RECORD_STATUS_NONE) {
            $this->getVideoChannelsService()->startRecord($video);
        } else {
            $this->getVideoChannelsService()->updateRecordStatusById($videoId, Constants::VIDEO_CHANNEL_RECORD_STATUS_NONE);
        }
    }

    public function summaryDateFileSizeAndHourList(array $conditions, $countDeleteData = false, $sort = 'DESC')
    {
        return $this->getRecordFileDao()->summaryDateFileSizeAndHourList($conditions, $countDeleteData, $sort);
    }

    public function summaryDateFileSizeAndHourListByMainId($mainId, $countDeleteData = false, $sort = 'DESC')
    {
        return $this->getRecordFileDao()->summaryDateFileSizeAndHourListByMainId($mainId, $countDeleteData, $sort);
    }

    /**
     * 批量删除录制文件
     *
     * @param array $conditions
     * @param bool $isSoftDel 是否安全删除，如果是安全删除则不会删除记录和文件
     * @param bool $isDelFile 是否删除文件
     * @return mixed
     */
    public function batchDeleteFiles(array $conditions, $isSoftDel = false, $isDelFile = true)
    {
        $files = $this->searchFiles($conditions, [], 0, PHP_INT_MAX);
        if (empty($files)) {
            return false;
        }
        $ids = ArrayToolkit::column($files, 'id');
        if ($isSoftDel) {
            $this->getRecordFileDao()->update(['ids' => $ids], ['deleted_time' => time()]);
        } else {
            $this->getRecordFileDao()->batchDelete(['ids' => $ids]);
        }

        if ($isDelFile) {
            foreach ($files as $file) {
                if (is_file($file['video_path'])) {
                    // TODO: 如果是集群 分离 部署 则必须调用接口;本地请以root权益账号运行webman
                    @unlink($file['video_path']);
                }
            }
        }

        return true;
    }

    /**
     * 获取 录制文件列表
     *
     * @param array $conditions
     * @param array $orderBy
     * @param $start
     * @param $limit
     * @param array $columns
     * @return mixed
     */
    public function searchFiles(array $conditions = [], array $orderBy = [], $start, $limit, array $columns = [])
    {
        return $this->getRecordFileDao()->search($conditions, $orderBy, $start, $limit, $columns);
    }

    /**
     * 记录录制文件
     *
     * @param array $recordFile
     * @return mixed
     */
    public function addRecordFile(array $recordFile)
    {
        if (!ArrayToolkit::requireds($recordFile, ['MainId', 'MediaServerId', 'MediaServerIp', 'ChannelName', 'DeviceId', 'ChannelId', 'StartTime', 'EndTime', 'Duration', 'VideoPath', 'FileSize', 'Vhost', 'Streamid', 'App', 'DownloadUrl', 'CreateTime', 'RecordDate', 'Undo'])) {
            return [-1, "缺少必要参数"];
        }
        $recordFile = ArrayToolkit::parts($recordFile, ['MainId', 'MediaServerId', 'MediaServerIp', 'ChannelName', 'DeviceId', 'ChannelId', 'StartTime', 'EndTime', 'Duration', 'VideoPath', 'FileSize', 'Vhost', 'Streamid', 'App', 'DownloadUrl', 'CreateTime', 'RecordDate', 'Undo']);
        if ($recordFile['Duration'] <= 0) {
            return [-1, "错误录制，录制时长为0"];
        }
        $videoChannel = $this->getVideoChannelsService()->getVideoChannelByMainId($recordFile['MainId']);
        if (empty($videoChannel)) {
            return [-1, "摄像头不存在"];
        }
        $this->getRecordFileDao()->create([
            'main_id' => $recordFile['MainId'],
            'media_server_id' => $recordFile['MediaServerId'],
            'media_server_ip' => $recordFile['MediaServerIp'],
            'channel_id' => $recordFile['ChannelId'],
            'channel_name' => $recordFile['ChannelName'],
            'device_id' => $recordFile['DeviceId'],
            'start_time' => strtotime($recordFile['StartTime']),
            'end_time' => strtotime($recordFile['EndTime']),
            'duration' => $recordFile['Duration'],
            'video_path' => $recordFile['VideoPath'],
            'file_size' => $recordFile['FileSize'],
            'vhost' => $recordFile['Vhost'],
            'stream_id' => $recordFile['Streamid'],
            'app' => $recordFile['App'],
            'download_url' => $recordFile['DownloadUrl'],
            'is_undo' => $recordFile['Undo'] ? 1 : 0,
            'record_date' => $recordFile['RecordDate'],
            'plan_id' => $videoChannel['record_plan_id'],
        ]);

        return [0, 'ok'];
    }

    public function getRecordFileDateListByMainId($mainId, $countDeleteData = false, $sort = 'DESC')
    {
        return $this->getRecordFileDao()->getRecordFileDateListByMainId($mainId, $countDeleteData, $sort);
    }

    public function getRecordFileSizeByMainId($mainId)
    {
        return $this->getRecordFileDao()->sumFileSize([
            'mainId' => $mainId,
            'deletedTime' => 0,
        ]);
    }


    public function deleteRecordPlanRangeById($id)
    {
        return $this->getRecordPlanRangeDao()->delete($id);
    }


    public function getRecordPlanById($id)
    {
        $item = $this->getRecordPlanDao()->get($id);
        $indexPlanRanges = $this->findIndexPlanRangeItemsByPlanIds([$id]);
        list($text, $ranges) = $this->parsePlanRanges($indexPlanRanges[$item['id']] ?? []);
        $item['plan_range_text'] = $text;
        $item['plan_ranges'] = $ranges;

        return $item;
    }

    public function countRecordPlans(array $conditions = [])
    {
        return $this->getRecordPlanDao()->count($conditions);
    }

    public function searchRecordPlans(array $conditions = [], array $orderBy = [], $start, $limit, array $columns = [])
    {
        $rows = $this->getRecordPlanDao()->search($conditions, $orderBy, $start, $limit, $columns);
        $ids = ArrayToolkit::column($rows, 'id');
        $indexPlanRanges = $this->findIndexPlanRangeItemsByPlanIds($ids);
        foreach ($rows as &$row) {
            list($text, $ranges) = $this->parsePlanRanges($indexPlanRanges[$row['id']] ?? []);
            $row['plan_range_text'] = $text;
            $row['plan_ranges'] = $ranges;
        }

        return $rows;
    }

    public function createRecordPlan(array $fields)
    {
        return $this->saveRecordPlan($fields);
    }

    public function updateRecordPlan($id, array $fields)
    {
        return $this->saveRecordPlan($fields, $id);
    }

    public function deleteRecordPlanRangeByRecordPlanId($planId)
    {
        return $this->getRecordPlanRangeDao()->batchDelete(['recordPlanId' => $planId]);
    }

    public function deleteRecordPlanById($id)
    {
        $bindVideoChannelCount = $this->getVideoChannelsService()->countVideoChannels(['recordPlanId' => $id]);
        if ($bindVideoChannelCount > 0) {
            throw RecordException::DELETE_PLAN_ERROR_HAS_VIDEOS();
        }
        $this->deleteRecordPlanRangeByRecordPlanId($id);
        return $this->getRecordPlanDao()->delete($id);
    }

    protected function parsePlanRanges($planRanges)
    {
        $rangeTextArr = [];
        $weekDayRanges = [];
        foreach ($planRanges as $range) {
            $weekday = $this->numberToWeekday($range['week_day']);
            if ($weekday) {
                $rangeTextArr[] = $weekday;
            }
        }
        $groupPlanRanges = ArrayToolkit::group($planRanges, 'week_day');
        for ($i = 1; $i <= 7; $i++) {
            if (!empty($groupPlanRanges[$i])) {
                $items = [];
                foreach ($groupPlanRanges[$i] as $value) {
                    $sKey = "s{$i}";
                    $eKey = "e{$i}";
                    $items[] = [$sKey => $value['start_time'], $eKey => $value['end_time']];
                }
                $weekDayRanges[$i - 1] = $items;
            } else {
                $weekDayRanges[$i - 1] = [];
            }
        }

        return [implode('|', $rangeTextArr), $weekDayRanges];
    }

    /**
     * @param $number 1-7
     */
    protected function numberToWeekday($number)
    {
        $days = [
            '星期一',
            '星期二',
            '星期三',
            '星期四',
            '星期五',
            '星期六',
            '星期日',
        ];

        return $days[$number - 1] ?? null;
    }

    public function findIndexPlanRangeItemsByPlanIds($ids)
    {
        $planRanges = $this->getRecordPlanRangeDao()->findInByPlanIds($ids);
        $items = array_map(function ($range) {
            return ArrayToolkit::parts($range, ['record_plan_id', 'week_day', 'start_time', 'end_time']);
        }, $planRanges);

        return ArrayToolkit::group($items, 'record_plan_id');
    }

    protected function saveRecordPlan($fields, $id = null)
    {
        $fields = ArrayToolkit::parts($fields, ['partner_id', 'name', 'status', 'remark', 'limit_space', 'limit_days', 'over_step_plan', 'plan_ranges']);
        if (!ArrayToolkit::requireds($fields, ['name', 'limit_space', 'over_step_plan'])) {
            throw RecordException::WRITE_RECORD_PLAN_FIELDS_ERROR();
        }

        if (!is_numeric($fields['limit_space'])) {
            throw RecordException::LIMIT_SPACE_TYPE_ERROR();
        }

        if (!is_numeric($fields['limit_days'])) {
            throw RecordException::LIMIT_DAYS_TYPE_ERROR();
        }

        if (!array_key_exists($fields['over_step_plan'], Constants::getRecordePlanOverStepTypes())) {
            throw RecordException::OVER_STEP_PLAN_VALUE_ERROR();
        }

        if (empty($fields['plan_ranges'])) {
            throw RecordException::RECORD_PLAN_RANGE_EMPTY_ERROR();
        }

        $fields['status'] = !isset($fields['status']) ? 0 : intval($fields['status']);

        $exist = null;
        $oldRecordPlan = null;
        $isAdd = true;

        if ($id) {
            $oldRecordPlan = $this->getRecordPlanDao()->get($id);
            if (empty($oldRecordPlan)) {
                throw RecordException::RECORD_PLAN_NOT_FOUND_ERROR();
            }
            $isAdd = false;
            if ($oldRecordPlan['name'] !== $fields['name']) {
                $exist = $this->getRecordPlanDao()->getByNameAndPartnerId($fields['name'], $fields['partner_id']);
            }
        } else {
            $exist = $this->getRecordPlanDao()->getByNameAndPartnerId($fields['name'], $fields['partner_id']);
        }

        if (!empty($exist)) {
            throw RecordException::RECORD_PLAN_NAME_ALREADY_ERROR();
        }

        empty($fields['limit_days']) && $fields['limit_days'] = self::DEFAULT_RECORD_PLAN_LIMIT_DAY;
        $fields['limit_space'] = 1024 * 1024 * 1024 * $fields['limit_space'];
        try {
            $this->beginTransaction();
            $planRanges = $fields['plan_ranges'];
            unset($fields['plan_ranges']);
            if (!$isAdd) {
                $recordPlan = $this->getRecordPlanDao()->update($id, $fields);
            } else {
                $recordPlan = $this->getRecordPlanDao()->create($fields);
            }
            $this->generatePlanRanges($recordPlan, $planRanges);
            if ($isAdd) {
                $this->getLogService()->info('record', 'record_plan', "创建录像计划{$fields['name']}成功", ['plan' => $recordPlan]);
            } else {
                $this->getLogService()->info('record', 'record_plan', "修改录像计划{$fields['name']}成功", [
                    'new_plan' => $recordPlan,
                    'old_plan' => $oldRecordPlan
                ]);
            }
            $this->commit();
            return $recordPlan;
        } catch (\Throwable $e) {
            if ($isAdd) {
                $this->getLogService()->error('record', 'record_plan', "创建录像计划{$fields['name']}失败", $fields);
            } else {
                $this->getLogService()->error('record', 'record_plan', "修改录像计划{$fields['name']}失败:{$e->getMessage()}", $fields);
            }
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 生成录像计划明细项
     *
     * @param $recordPlan
     * @param $ranges
     */
    protected function generatePlanRanges($recordPlan, $ranges)
    {
        // TODO: 用 record_plan_id + week_day 标记唯一的计划明细项
        $this->getRecordPlanRangeDao()->batchDelete([
            'recordPlanId' => $recordPlan['id'],
        ]);

        $addRows = [];
        foreach ($ranges as $index => $weekDayRanges) {
            $weekDay = $index + 1;
            if (empty($weekDayRanges)) {
                continue;
            }
            foreach ($weekDayRanges as $timeSpan) {
                $sKey = sprintf("s%s", $weekDay);
                $eKey = sprintf("e%s", $weekDay);
                if (empty($timeSpan) || !isset($timeSpan[$sKey]) || !isset($timeSpan[$eKey])) {
                    continue;
                }
                if (!strtotime($timeSpan[$sKey]) || !strtotime($timeSpan[$eKey])) {
                    throw RecordException::PLAN_RANGE_TIME_ERROR();
                }

                if (strtotime($timeSpan[$sKey]) >= strtotime($timeSpan[$eKey])) {
                    throw RecordException::PLAN_RANGE_TIME_LIMIT_ERROR();
                }
                $addRows[] = [
                    'week_day' => $weekDay,
                    'record_plan_id' => $recordPlan['id'],
                    'start_time' => $timeSpan[$sKey],
                    'end_time' => $timeSpan[$eKey],
                ];
            }

        }

        $this->getRecordPlanRangeDao()->batchCreate($addRows);
    }

    /**
     * @return RecordPlanDao
     */
    protected function getRecordPlanDao()
    {
        return $this->createDao('Record:RecordPlanDao');

    }

    /**
     * @return RecordPlanRangeDao
     */
    protected function getRecordPlanRangeDao()
    {
        return $this->createDao('Record:RecordPlanRangeDao');
    }

    /**
     * @return RecordFileDao
     */
    protected function getRecordFileDao()
    {
        return $this->createDao('Record:RecordFileDao');
    }

    /**
     * @return VideoChannelsService
     */
    protected function getVideoChannelsService()
    {
        return $this->createService('VideoChannels:VideoChannelsService');
    }

    /**
     * @return SystemLogService
     */
    protected function getLogService()
    {
        return $this->createService('SystemLog:SystemLogService');
    }
}

