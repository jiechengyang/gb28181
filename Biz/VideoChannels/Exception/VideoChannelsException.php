<?php

namespace Biz\VideoChannels\Exception;

use support\exception\AbstractException;

class VideoChannelsException extends AbstractException 
{
    const VIDEO_CHANNEL_NOT_FOUND = 4042031;

    const VIDEO_CHANNEL_REGISTER_FIELDS_FAILED = 5002031;
    const VIDEO_CHANNEL_ACTIVE_FIELDS_FAILED = 5002032;
    const VIDEO_CHANNEL_NOT_ACTIVE = 5002033;
    const VIDEO_CHANNEL_STREAM_LIVE_PROTOCOL_NOT_FOUND = 5002034;
    const VIDEO_CHANNEL_STREAM_LIVE_FAILED = 5002035;
    const VIDEO_CHANNEL_PTZ_CONTROL_PARAMS_FAILED = 5002036;
    const VIDEO_CHANNEL_STOP_LIVE_FAILED = 5002037;

    public function __construct($code)
    {
        $this->setMessages();
        parent::__construct($code);
    }

    public function setMessages()
    {
        $this->messages = [
            self::VIDEO_CHANNEL_NOT_FOUND => '摄像头不存在',
            self::VIDEO_CHANNEL_REGISTER_FIELDS_FAILED => '同步摄像头参数错误',
            self::VIDEO_CHANNEL_ACTIVE_FIELDS_FAILED => '激活摄像头参数错误',
            self::VIDEO_CHANNEL_NOT_ACTIVE => '摄像头未激活，请先激活设备',
            self::VIDEO_CHANNEL_STREAM_LIVE_PROTOCOL_NOT_FOUND => '请求直播失败，播放协议不支持',
            self::VIDEO_CHANNEL_STREAM_LIVE_FAILED => '请求直播失败',
            self::VIDEO_CHANNEL_PTZ_CONTROL_PARAMS_FAILED => '请求云台控制失败，控制参数不正确',
            self::VIDEO_CHANNEL_STOP_LIVE_FAILED => '请求关闭直播失败'
        ];
    }

}
