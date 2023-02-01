<?php

namespace Biz\VideoRecorder\Exception;

use support\exception\AbstractException;

class VideoRecorderException extends AbstractException 
{
    const SIZ_DEVICE_PARAMS_FAILED = 5001030;

    const VIDEO_RECORDER_NOT_FOUND = 4041031;

    public function __construct($code)
    {
        $this->setMessages();
        parent::__construct($code);
    }

    public function setMessages()
    {
        $this->messages = [
            self::SIZ_DEVICE_PARAMS_FAILED => '同步设备信息参数不正确',
            self::VIDEO_RECORDER_NOT_FOUND => '设备类型不一致，非录像机'
        ];
    }

}
