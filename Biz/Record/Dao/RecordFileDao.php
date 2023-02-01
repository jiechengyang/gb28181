<?php


namespace Biz\Record\Dao;


use Codeages\Biz\Framework\Dao\AdvancedDaoInterface;

interface RecordFileDao extends AdvancedDaoInterface
{
    /**
     * 计算存储文件大小
     *
     * @param array $conditions
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public function sumFileSize(array $conditions);

    /**
     * 获取某个设备录制的天数列表
     *
     * @param $mainId
     * @param false $countDeleteData
     * @param string $sort
     * @return mixed[]
     */
    public function getRecordFileDateListByMainId($mainId, $countDeleteData = false, $sort = 'DESC');

    /**
     * 按天汇总某个设备每天录制的总时长（近视值，单位：小时）、总容量（近视值，单位：gb）
     *
     * @param string $mainId
     * @param bool $countDeleteData 是否计算逻辑删除的数据
     * @param string $sort 排序方式 DESC：按时间倒序；ASC：按时间正序
     * @return array[]
     */
    public function summaryDateFileSizeAndHourListByMainId($mainId, $countDeleteData = false, $sort = 'DESC');

    /**
     * 按天汇总所有设备每天录制的总时长（近视值，单位：小时）、总容量（近视值，单位：gb）
     *
     * @param array $conditions 查询条件
     * @param bool $countDeleteData 是否计算逻辑删除的数据
     * @param string $sort 排序方式 DESC：按时间倒序；ASC：按时间正序
     * @return array[]
     */
    public function summaryDateFileSizeAndHourList(array $conditions, $countDeleteData = false, $sort = 'DESC');

    /**
     * 获取当前录制模板录制的天数列表
     *
     * @param array $conditions
     * @param string $sort
     * @return mixed[]
     */
    public function getRecordFileDateList(array $conditions = [], $sort = 'DESC');
}