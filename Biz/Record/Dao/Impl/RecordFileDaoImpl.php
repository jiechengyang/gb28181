<?php


namespace Biz\Record\Dao\Impl;


use Biz\Record\Dao\RecordFileDao;
use Codeages\Biz\Framework\Dao\AdvancedDaoImpl;

/**
 *
 *
 * Class RecordFileDaoImpl
 *
 * @method  \Doctrine\DBAL\Connection db()
 * @package Biz\Record\Dao\Impl
 */
class RecordFileDaoImpl extends AdvancedDaoImpl implements RecordFileDao
{

    protected $table = 'smp_record_file';

    /**
     * 获取某个设备录制的天数列表
     *
     * @param $mainId
     * @param false $countDeleteData
     * @param string $sort
     * @return mixed[]
     */
    public function getRecordFileDateListByMainId($mainId, $countDeleteData = false, $sort = 'DESC')
    {
        $where = "`main_id` = '$mainId'";
        if ($countDeleteData) {
            $where = ' AND deleted_time = 0';
        }

        $sql = "SELECT record_date FROM " . $this->table() . " WHERE {$where}  GROUP BY record_date ORDER BY `record_date` {$sort}";

        return $this->db()->fetchAll($sql);
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
        $builder = $this->createQueryBuilder($conditions);

        return $builder
            ->select('record_date')
            ->groupBy('record_date')
            ->orderBy('record_date', $sort)
            ->execute()->fetchAll();
    }

    /**
     * 按天汇总某个设备每天录制的总时长（近视值，单位：小时）、总容量（近视值，单位：gb）
     *
     * @param string $mainId
     * @param bool $countDeleteData 是否计算逻辑删除的数据
     * @param string $sort 排序方式 DESC：按时间倒序；ASC：按时间正序
     * @return array[]
     */
    public function summaryDateFileSizeAndHourListByMainId($mainId, $countDeleteData = false, $sort = 'DESC')
    {
        $where = "`main_id` = '$mainId'";
        if ($countDeleteData) {
            $where = ' AND deleted_time = 0';
        }

        $sql = "SELECT record_date,convert(SUM(duration)/60/60,decimal(10,2)) as hours,convert(sum(file_size)/1024/1024/1024,decimal(10,2)) as sizes	 FROM " . $this->table() . " WHERE {$where}  GROUP BY record_date ORDER BY `record_date` {$sort}";

        return $this->db()->fetchAll($sql);
    }

    /**
     * 按天汇总所有设备每天录制的总时长（近视值，单位：小时）、总容量（近视值，单位：gb）
     *
     * @param bool $countDeleteData 是否计算逻辑删除的数据
     * @param string $sort 排序方式 DESC：按时间倒序；ASC：按时间正序
     * @return array[]
     */
    public function summaryDateFileSizeAndHourList(array $conditions, $countDeleteData = false, $sort = 'DESC')
    {
        if (!$countDeleteData) {
            $conditions['deletedTime'] = 0;
        }

        $builder = $this->createQueryBuilder($conditions)->select([
            'record_date',
            'convert(SUM(duration)/60/60,decimal(10,2)) as hours',
            'convert(sum(file_size)/1024/1024/1024,decimal(10,2)) as sizes'
        ])->addGroupBy('record_date')
            ->addOrderBy('record_date', $sort);

        return $builder->execute()->fetchAll();
    }

    /**
     * @param array $conditions
     * @return int
     * @throws \Doctrine\DBAL\Exception
     */
    public function sumFileSize(array $conditions)
    {
        $builder = $this->createQueryBuilder($conditions)
            ->select('SUM(file_size)');

        return (int)$builder->execute()->fetchColumn(0);
    }

    public function declares()
    {
        return [
            'serializes' => [
            ],
            'orderbys' => [
                'id',
                'created_time'
            ],
            'conditions' => [
                'id = :id',
                'id > :id_GT',
                'id IN ( :ids)',
                'id NOT IN ( :noIds)',
                'main_id = :mainId',
                'deleted_time = :deletedTime',
                'duration > :duration_GT',
                'record_date = :recordDate',
                'record_date >= :recordDate_GE',
                'record_date > :recordDate_GT',
                'record_date <= :recordDate_LE',
                'record_date < :recordDate_LT',
                'plan_id = :planId',
                'plan_id IN ( :planIds)',
            ],
            'timestamps' => [
                'created_time',
                'updated_time',
            ],
        ];
    }
}