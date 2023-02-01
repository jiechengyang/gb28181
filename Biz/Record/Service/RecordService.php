<?php

namespace Biz\Record\Service;

interface RecordService
{
    public function getRecordPlanById($id);

    public function createRecordPlan(array $fields);

    public function updateRecordPlan($id, array $fields);

    public function deleteRecordPlanById($id);

    public function deleteRecordPlanRangeByRecordPlanId($planId);

    public function countRecordPlans(array $conditions = []);

    public function searchRecordPlans(array $conditions = [], array $orderBy = [], $start, $limit, array $columns = []);

    public function deleteRecordPlanRangeById($id);

    public function getRecordFileSizeByMainId($mainId);

    public function getRecordFileDateListByMainId($mainId, $countDeleteData = false, $sort = 'DESC');

    public function findIndexPlanRangeItemsByPlanIds($ids);

    /**
     * 记录录制文件
     *
     * @param array $recordFile
     * @return mixed
     */
    public function addRecordFile(array $recordFile);

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
    public function searchFiles(array $conditions = [], array $orderBy = [], $start, $limit, array $columns = []);

    /**
     * 批量删除录制文件
     *
     * @param array $conditions
     * @param bool $isSoftDel 是否安全删除，如果是安全删除则不会删除记录和文件
     * @param bool $isDelFile 是否删除文件
     * @return mixed
     */
    public function batchDeleteFiles(array $conditions, $isSoftDel = false,$isDelFile = true);

    /**
     * 按天汇总某个设备每天录制的总时长（近视值，单位：小时）、总容量（近视值，单位：gb）
     *
     * @param string $mainId
     * @param bool $countDeleteData 是否计算逻辑删除的数据
     * @param string $sort 排序方式 DESC：按时间倒序；ASC：按时间正序
     * @return array[]
     * @deprecated v2
     */
    public function summaryDateFileSizeAndHourListByMainId($mainId, $countDeleteData = false, $sort = 'DESC');

    /**
     * 按天汇总所有设备每天录制的总时长（近视值，单位：小时）、总容量（近视值，单位：gb）
     *
     * @param array $conditions  条件
     * @param bool $countDeleteData 是否计算逻辑删除的数据
     * @param string $sort 排序方式 DESC：按时间倒序；ASC：按时间正序
     * @return array[]
     */
    public function summaryDateFileSizeAndHourList(array $conditions, $countDeleteData = false, $sort = 'DESC');

    /**
     * @param $videoId
     * @return mixed
     */
    public function delPlayBack($videoId);

    /**
     * @param $videoId
     * @return mixed
     */
    public function closePlayBack($videoId);

    public function startPlayBack($videoId);

    public function getRecordFile($id);

    /**
     * 获取当前录制模板录制的天数列表
     *
     * @param array $conditions
     * @param string $sort
     * @return mixed[]
     */
    public function getRecordFileDateList(array $conditions = [], $sort = 'DESC');

    /**
     * 根据条件计算录制文件大小
     *
     * @param array $conditions
     * @return mixed
     */
    public function getRecordFileSize(array $conditions = []);
}
