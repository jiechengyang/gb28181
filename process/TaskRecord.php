<?php


namespace process;


use Biz\Constants;
use Biz\Record\Service\RecordService;
use Biz\VideoChannels\Service\VideoChannelsService;
use Codeages\Biz\Framework\Util\ArrayToolkit;
use support\bootstrap\Log;
use Workerman\Timer;
use Workerman\Worker;

class TaskRecord extends AbstractProcess
{
    private $processNum;

    private static $recordingItems = [];

    private $logMessages = [];


    public function onWorkerStart(Worker $worker)
    {
        if (!\envHelper('ENABLE_AUTO_RECORD', 1)) {
            return;
        }
        $this->processNum = envHelper('TASK_RECORD_PROCESS_NUM', 3);
        $worker->onWorkerStop = function ($worker) {
            $this->clearAllRecordIngVideo($worker->id);
        };
        Timer::add(1, function () use ($worker) {
            try {
                $this->keepRecord($worker->id);
            } catch (\Throwable $e) {
                var_dump($e->getMessage());
            }
        });
    }

    protected function spiltVideoChannelsByWork($videoChannelList, $workPrimaryKey)
    {
        $limit = ceil(count($videoChannelList) / $this->processNum);
        if ($limit <= 0) {
            return [];
        }
        $chunkList = array_chunk($videoChannelList, $limit);
        if (!isset($chunkList[$workPrimaryKey])) {
            return [];
        }

        return $chunkList[$workPrimaryKey];
    }

    protected function getRecordWorkerKey($workPrimaryKey)
    {
        return 'worker_' . $workPrimaryKey;
    }

    protected function clearAllRecordIngVideo($workPrimaryKey)
    {
        $this->logMessages = [];
        $channels = $this->getRecordIngVideoChannels();
        $videos = $this->spiltVideoChannelsByWork($channels, $workPrimaryKey);
        $recordWorkerKey = $this->getRecordWorkerKey($workPrimaryKey);
        if (!empty($videos)) {
            echo '===退出后，清理还在录制的视频监控===', PHP_EOL;
            foreach ($videos as $channel) {
                $vKey = $channel['main_id'];
                if (isset(self::$recordingItems[$recordWorkerKey][$vKey])) {
                    unset(self::$recordingItems[$recordWorkerKey][$vKey]);
                }
                $this->getVideoChannelsService()->stopRecord($channel);
            }
        }
    }

    /**
     *
     * @param $workPrimaryKey
     * @todo 天数限制：实现保留近n天的视频（如果视频录制天数超过了n天，则删除到当前时间-n天前的时间的所有这个摄像头的视频）；空间限制：如果空间超出上限则根据条件停止录制或者删除文件
     * 压缩视频：https://zhuanlan.zhihu.com/p/255042580
     */
    protected function keepRecord($workPrimaryKey)
    {
        $planList = $this->getPlans();
        if (empty($planList)) {
            $this->loopLog("[进程$workPrimaryKey]暂无可用录像计划模板");
            return;
        }
        $planIds = ArrayToolkit::column($planList, 'id');
        $videoChannelList = $this->getOnlineVideoChannels($planIds);
        $plans = ArrayToolkit::index($planList, 'id');
        if (empty($videoChannelList)) {
//            echo '暂无已绑定录像计划并激活的摄像头', PHP_EOL;
            $this->loopLog("[进程$workPrimaryKey]暂无已绑定录像计划并激活的摄像头");
//            $this->log('暂无已绑定录像计划并激活的摄像头');
            return;
        }

        $videos = $this->spiltVideoChannelsByWork($videoChannelList, $workPrimaryKey);
        $recordWorkerKey = $this->getRecordWorkerKey($workPrimaryKey);

        foreach ($videos as $videoChannel) {
            // 是否停止录制标识
            $stopIt = false;
            $vKey = $videoChannel['main_id'];
            $plan = $plans[$videoChannel['record_plan_id']];
            if ($plan['status'] != 1) {
                $stopIt = true;
                $this->loopLog($vKey . '录像计划已关闭');
                if (isset(self::$recordingItems[$recordWorkerKey][$vKey])) {
                    unset(self::$recordingItems[$recordWorkerKey][$vKey]);
                }
                continue;
            } else {
                $inRange = $this->checkTimeRange($plan);
                // 表示不在时间范围内
                if (!$inRange) {
                    $stopIt = true;
                    $this->loopLog($vKey . '不在时间范围内,无法录制');
                    if (isset(self::$recordingItems[$recordWorkerKey][$vKey])) {
                        unset(self::$recordingItems[$recordWorkerKey][$vKey]);
                        echo '停止录制监控：', $videoChannel['main_id'], PHP_EOL;
                        $this->getVideoChannelsService()->stopRecord($videoChannel);
                    }
                    continue;
                }
            }

            if ($videoChannel['record_status'] == Constants::VIDEO_CHANNEL_RECORD_STATUS_CLOSE) {
                $stopIt = true;
                $this->loopLog($vKey . '摄像头已设置为：关闭录像');
                if (isset(self::$recordingItems[$recordWorkerKey][$vKey])) {
                    unset(self::$recordingItems[$recordWorkerKey][$vKey]);
                    echo '停止录制监控：', $videoChannel['main_id'], PHP_EOL;
                    $this->getVideoChannelsService()->stopRecord($videoChannel);
                }

                continue;
            }

            // 得到记录天数列表
//            $fileDateList = $this->getRecordFileDateListByMainId($videoChannel['main_id']);
            $fileDateList = $this->getRecordService()->getRecordFileDateList([
                'planId' => $videoChannel['record_plan_id'],
                'deletedTime' => 0,
            ]);
            // 得到文件总长度
//            $fileSize = $this->getRecordFileSizeByMainId($videoChannel['main_id']);
            $fileSize = $this->getRecordService()->getRecordFileSize([
                'planId' => $videoChannel['record_plan_id'],
                'deletedTime' => 0,
            ]);
            // 表示记录天数已经超过了 最大天数限制 或者 记录文件大小超出了 最大存储限制
            $fileCount = count($fileDateList);
            if ($fileCount > $plan['limit_days']) {
                // 清理操作
                $stopIt = true;
                $this->saveLastDaysRecord($recordWorkerKey, $plan, $videoChannel, $fileDateList);
            }

            if ($fileSize > $plan['limit_space']) {
                // 清理操作
                $stopIt = true;
                $this->stopRecordHandler($recordWorkerKey, $plan, $videoChannel, $fileSize, $fileDateList);
            }

            if (!$stopIt) {
                if (!empty(self::$recordingItems[$recordWorkerKey][$vKey])) {
                    $this->loopLog('摄像头视频' . $vKey . '正在录制中');
                } else {
                    $code = $this->getVideoChannelsService()->startRecord($videoChannel);
                    if ($code !== 0) {
                        echo $vKey, '调用录制接口失败', PHP_EOL;
                    } else {
                        self::$recordingItems[$recordWorkerKey][$vKey] = true;
                        echo $vKey, '开始录制', PHP_EOL;
                    }
                }
            } else {
                if (isset(self::$recordingItems[$recordWorkerKey][$vKey])) {
                    unset(self::$recordingItems[$recordWorkerKey][$vKey]);
                    echo '需要停止录制监控：', $videoChannel['main_id'], PHP_EOL;
                    $this->getVideoChannelsService()->stopRecord($videoChannel);
                }
            }

        }
    }

    /**
     * 保留近n天的数据
     */
    protected function saveLastDaysRecord($recordWorkerKey, $plan, $videoChannel, $fileDateList)
    {
        $count = count($fileDateList);
        $diffCount = $count - $plan['limit_days'];
        echo '超出录制天数：', $diffCount, '天', PHP_EOL;
        $lastedDate = end($fileDateList);
        $startDate = current($fileDateList);
        echo '开始录制时间:', $startDate, '|最后录制时间:', $lastedDate, PHP_EOL;
        // 删除开始录制的那天
        // 天数限制3天, 我录制到第四天：21 22 23 24 最后保留： 22 23 24
        $this->getRecordService()->batchDeleteFiles(['recordDate' => $startDate]);
        echo '已清理开始录制那天的数据', PHP_EOL;
    }

    /**
     * 超过空间限制后停止操作处理
     *
     * @param $recordWorkerKey
     * @param $plan
     * @param $videoChannel
     * @param $currentFileSize
     */
    protected function stopRecordHandler($recordWorkerKey, $plan, $videoChannel, $currentFileSize, $fileDateList)
    {
        $vKey = $videoChannel['main_id'];
        if ($plan['over_step_plan'] === 'delFile') {
            echo '清理方式:超过限制后删除文件', PHP_EOL;
            if ($currentFileSize > $plan['limit_space']) {
                $startDate = current($fileDateList);
                echo '超出限制条件：记录文件大小超出了 最大存储限制，删除：', $startDate, '的数据', PHP_EOL;
                // 例如限制：100G
                // 21号 20G
                // 22号 40G
                // 23号 60G
                // 24号 80G
                // 25号 100G
                // 26号 110G ---要删除21号的（采用安全删除）
//                $this->getRecordService()->batchDeleteFiles(['recordDate_LE' => date('Y-m-d')], true, true);
                $this->getRecordService()->batchDeleteFiles(['recordDate' => $startDate], true, true);
            }
            if (isset(self::$recordingItems[$recordWorkerKey][$vKey])) {
                unset(self::$recordingItems[$recordWorkerKey][$vKey]);
            }
            $this->getVideoChannelsService()->stopRecord($videoChannel);
        } elseif ($plan['over_step_plan'] == 'stopDvr') {
            echo '清理方式:超过限制后停止录制', PHP_EOL;
            if ($currentFileSize > $plan['limit_space']) {
                echo '超出限制条件：记录文件大小超出了 最大存储限制', PHP_EOL;
                if (isset(self::$recordingItems[$recordWorkerKey][$vKey])) {
                    unset(self::$recordingItems[$recordWorkerKey][$vKey]);
                }
//                $this->getRecordService()->batchDeleteFiles(['recordDate_LE' => date('Y-m-d')], true, false);
                $this->getVideoChannelsService()->stopRecord($videoChannel);

            }
        }
    }

    /**
     * 检查是否在时间范围内
     *
     * @param $plan
     * @return false
     */
    protected function checkTimeRange($plan)
    {
        if (empty($plan['time_range_list'])) {
            return false;
        }
        $flag = false;
        foreach ($plan['time_range_list'] as $timeRange) {
            // 是否再今天
            if ($timeRange['week_day'] !== date('N')) {
                continue;
            }

            $st = $timeRange['start_time'];
            $et = $timeRange['end_time'];
            $now = date('H:i');
            if ($st <= $now && $et >= $now) {
                $flag = true;
                break;
            }
        }

        return $flag;
    }

    /**
     * @param $mainId
     */
    protected function getRecordFileDateListByMainId($mainId)
    {
        return $this->getRecordService()->getRecordFileDateListByMainId($mainId);
    }


    /**
     * 获取录制文件总长度
     *
     * @param $mainId
     */
    protected function getRecordFileSizeByMainId($mainId)
    {
        return $this->getRecordService()->getRecordFileSizeByMainId($mainId);
    }

    protected function loopLog($message, $context = [])
    {
        if (!in_array($message, $this->logMessages)) {
            if (\Config('app.debug')) {
                echo $message, PHP_EOL;
            } else {
                $this->getLogger()->info($message, $context);
            }

            $this->logMessages[] = $message;
        }
    }

    /**
     * @return \Monolog\Logger|null
     */
    protected function getLogger()
    {
        return Log::channel('task-record');
    }

    protected function getPlans()
    {
        $plans = $this->getRecordService()->searchRecordPlans([], [], 0, PHP_INT_MAX);
        $planIds = ArrayToolkit::column($plans, 'id');
        $planRanges = $this->getRecordService()->findIndexPlanRangeItemsByPlanIds($planIds);
        foreach ($plans as &$plan) {
            $plan['time_range_list'] = !empty($planRanges[$plan['id']]) ? $planRanges[$plan['id']] : [];
        }

        return $plans;
    }

    /**
     *
     * 需要用一个进程任务去维护状态
     * @param $planIds
     * @return mixed
     */
    protected function getOnlineVideoChannels($planIds)
    {
        return $this->getVideoChannelsService()
            ->searchVideoChannels([
                'deviceStatus' => Constants::DEVICE_STATUS_ONLINE,
                'enabled' => 1,
                'planIds' => $planIds,
            ], [], 0, PHP_INT_MAX);
    }

    /**
     * 获取正在录制的视频监控
     *
     * @return array[]
     */
    protected function getRecordIngVideoChannels()
    {
        return $this->getVideoChannelsService()
            ->searchVideoChannels([
                'deviceStatus' => Constants::DEVICE_STATUS_ONLINE,
                'enabled' => 1,
                'recordStatus' => 1,
            ], [], 0, PHP_INT_MAX);
    }

    /**
     * @return RecordService
     */
    protected function getRecordService()
    {
        return $this->getBiz()->service('Record:RecordService');
    }

    /**
     * @return VideoChannelsService
     */
    protected function getVideoChannelsService()
    {
        return $this->getBiz()->service('VideoChannels:VideoChannelsService');
    }
}