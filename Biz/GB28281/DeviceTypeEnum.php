<?php


namespace Biz\GB28281;

use support\exception\InvalidParamException;

/**
 *
 * 设备类型编码
 * Class DeviceTypeEnum
 * @package Biz\GB28281
 */
class DeviceTypeEnum
{
    /**
     * DVR编码
     */
    const TYPE_DVR = 111;

    /**
     * 视频服务器编码
     */
    const TYPE_VIDEO_SERVER = 112;

    /**
     * 编码器编码
     */
    const TYPE_ENCODER_SERVER = 113;

    /**
     * 解码器编码
     */
    const TYPE_DECODER_SERVER = 114;

    /**
     * 视频切换矩阵编码
     */
    const  TYPE_VIDEO_MATRIX = 115;

    /**
     * 音频切换矩阵编码
     */
    const TYPE_AUDIO_MATRIX = 116;

    /**
     * 报警控制器编码
     */
    const TYPE_ALARM_CONTROL = 117;

    /**
     * 网络视频录像机(NVR)编码
     */
    const TYPE_NVR = 118;

    /**
     * 混合硬盘录像机(HVR)编码
     */
    const TYPE_HVR = 119;

    /**
     * 摄像机编码
     */
    const TYPE_CAMERA = 131;

    /**
     * 网络摄像机(IPC)编码
     */
    const TYPE_IPC = 132;

    /**
     * 显示器编码
     */
    const TYPE_DISPLAY = 133;

    /**
     * 报警输 入 设 备 编 码 (如 红 外、烟 感、门禁等报警设备)
     */
    const TYPE_ALARM_INPUT = 134;

    /**
     * @param null $key
     * @return mixed
     */
    public static function videoRecorderEnums($key = null)
    {
        $enums = [
            self::TYPE_DVR => '数字视频录像机(DVR)',
            self::TYPE_NVR => '网络视频录像机(NVR)',
            self::TYPE_HVR => '混合硬盘录像机(HVR)',
        ];

        return self::getItems($enums, $key);
    }

    /**
     *
     * 摄像头
     * @param null $key
     * @return mixed
     */
    public static function cameraEnums($key = null)
    {
        $enums = [
            self::TYPE_CAMERA => '普通摄像机',
            self::TYPE_IPC => '网络摄像机(IPC)'
        ];

        return self::getItems($enums, $key);
    }


    private static function getItems($items, $key = null)
    {
        if ($key !== null) {
            if (key_exists($key, $items)) {
                return $items[$key];
            }

            return null;
//            throw new InvalidParamException('Unknown key:' . $key);
        }
        return $items;
    }
}