<?php

namespace Biz\Record\Exception;

use support\exception\AbstractException;

class RecordException extends AbstractException 
{
    const WRITE_RECORD_PLAN_FIELDS_ERROR = 4006001;
    const RECORD_PLAN_NAME_ALREADY_ERROR = 4006002;
    const RECORD_PLAN_RANGE_EMPTY_ERROR = 4006003;
    const RECORD_PLAN_NOT_FOUND_ERROR = 4046001;
    const LIMIT_SPACE_TYPE_ERROR = 4006004;
    const LIMIT_DAYS_TYPE_ERROR = 4006005;
    const OVER_STEP_PLAN_VALUE_ERROR = 4006006;
    const PLAN_RANGE_FIELDS_ERROR = 4006007;
    const PLAN_RANGE_TIME_ERROR = 4006008;
    const PLAN_RANGE_TIME_LIMIT_ERROR = 4006009;
    const PLAN_RANGE_WEEK_ERROR = 4006010;
    const DELETE_PLAN_ERROR_HAS_VIDEOS = 4006011;
    const CLEAR_VIDEO_RECORD_ERROR_NOT_FOUND_VIDEO = 4006002;

    public function __construct($code)
    {
        $this->setMessages();
        parent::__construct($code);
    }

    public function setMessages()
    {
        $this->messages = [
            self::WRITE_RECORD_PLAN_FIELDS_ERROR => '操作失败，缺少必要参数',
            self::RECORD_PLAN_NAME_ALREADY_ERROR => '操作失败，录像计划名称已被使用',
            self::RECORD_PLAN_RANGE_EMPTY_ERROR => '操作失败，录像计划明细规则为空',
            self::RECORD_PLAN_NOT_FOUND_ERROR => '录像计划不存在',
            self::LIMIT_SPACE_TYPE_ERROR => '操作失败，空间大小限制必须是数字',
            self::OVER_STEP_PLAN_VALUE_ERROR => '操作失败，超出天数限制后执行动作类型不存在',
            self::LIMIT_DAYS_TYPE_ERROR => '操作失败, 天数限制必须是数字',
            self::PLAN_RANGE_FIELDS_ERROR => '操作失败，计划明细项存在参数错误',
            self::PLAN_RANGE_TIME_ERROR => '操作失败，计划明细项开始时间或结束时间格式错误',
            self::PLAN_RANGE_TIME_LIMIT_ERROR => '操作失败，计划明细项开始时间和结束时间返回错误',
            self::PLAN_RANGE_WEEK_ERROR => '操作失败，计划明细项所处星期参数错误',
            self::DELETE_PLAN_ERROR_HAS_VIDEOS => '删除失败，计划已绑定摄像头，请先解绑摄像头',
            self::CLEAR_VIDEO_RECORD_ERROR_NOT_FOUND_VIDEO => '清空录像失败，摄像头不存在'
        ];
    }

}
