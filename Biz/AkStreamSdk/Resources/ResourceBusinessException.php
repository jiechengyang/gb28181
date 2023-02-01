<?php


namespace Biz\AkStreamSdk\Resources;


use support\exception\AbstractException;

class ResourceBusinessException extends AbstractException
{
    const MEDIA_SERVER_EMPTY_MEDIA_SERVER_ID = 4049001;
    const MEDIA_SERVER_VIDEO_CHANNEL_FAILED_ORDER_BY = 5009002;
    const MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_IPV4ADDRESS = 5009003;
    const MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_DEVICEID = 5009004;
    const MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_CHANNELID = 5009005;
    const MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_MEDIA_SERVERID = 5009006;
    const MEDIA_SERVER_MODIFY_VIDEO_CHANNEL_EMPTY_MAIN_ID = 5009007;
    const MEDIA_SERVER_CUT_OR_MERGE_VIDEO_FILE_PARAMS_FAILED = 5009008;
    const SIP_GATEWAY_PTZ_CONTROL_COMMAND_TYPE_FAILED = 5009009;
    const SIP_GATEWAY_PTZ_CONTROL_SPEED_FAILED = 5009010;

    public function setMessages()
    {
        return [
            self::MEDIA_SERVER_EMPTY_MEDIA_SERVER_ID => '获取MediaServer信息失败：mediaSeverId不能为空！',
            self::MEDIA_SERVER_VIDEO_CHANNEL_FAILED_ORDER_BY => '获取未激活的音视频流通道列表失败：请传入正确的orderBy参数',
            self::MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_IPV4ADDRESS => '添加音视频通道失败ipv4参数必须提供',
            self::MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_DEVICEID => '添加音视频通道失败：设备id参数必须提供',
            self::MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_CHANNELID => '添加音视频通道失败：设备通道id参数必须提供',
            self::MEDIA_SERVER_ADD_VIDEO_CHANNEL_EMPTY_MEDIA_SERVERID => '添加音视频通道失败：流媒体服务器id参数必须提供',
            self::MEDIA_SERVER_MODIFY_VIDEO_CHANNEL_EMPTY_MAIN_ID => '修改音视频通道失败：mainId不能为空',
            self::MEDIA_SERVER_CUT_OR_MERGE_VIDEO_FILE_PARAMS_FAILED => '添加裁剪合并任务失败: 参数错误',
            self::SIP_GATEWAY_PTZ_CONTROL_COMMAND_TYPE_FAILED => '云台控制失败：指令不存在',
            self::SIP_GATEWAY_PTZ_CONTROL_SPEED_FAILED => '云台控制失败：速度参数为大于1的整数',
        ];
    }
}