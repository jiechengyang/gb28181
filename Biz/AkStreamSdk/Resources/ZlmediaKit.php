<?php

namespace Biz\AkStreamSdk\Resources;

interface ZlmediaKit 
{
    /**
     * 获取RTP服务器列表
     *
     * @return array[]
     */
    public function listRtpServer();

    /**
     * 获取rtp推流信息
     *
     * @param [type] $streamId
     * @return array|null
     */
    public function getRtpInfo($streamId);

    /**
     * 流是否在线
     *
     * @param [type] $schema
     * @param [type] $streamId
     * @param string $app
     * @param string $vhost
     * @return boolean
     */
    public function isMediaOnline($schema, $streamId, $app = 'rtp', $vhost = '__defaultVhost__');

    /**
     * 关闭rtp服务器
     *
     * @param [type] $streamId
     * @return void
     */
    public function closeRtpServer($streamId);
}