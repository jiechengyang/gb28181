<?php


namespace Biz\LiveProvider\Strategy\Impl;


use Biz\LiveProvider\Strategy\LiveProvider;
use Biz\LiveProvider\Strategy\LiveProviderStrategy;
use Biz\Ys7Sdk\OpenYs7;

class Ys7Strategy extends LiveProviderStrategy implements LiveProvider
{
    public function openLiveWithCameras(array $conditions, array $options = [])
    {

    }

    public function activeAndOpenLiveWithCameras(array $conditions, $sort, $offset, $limit, $options = [])
    {
        // TODO: Implement activeAndOpenLiveWithCameras() method.
    }

    public function closeLiveWithCameras(array $conditions, array $options = [])
    {
        // TODO: Implement closeLiveWithCameras() method.
    }

    public function getDevices()
    {
        // TODO: Implement getDevices() method.
    }

    public function getCameras()
    {
        // TODO: Implement getCameras() method.
    }

    public function getLiveUrl($code, array $options = [])
    {
        // TODO: Implement getLiveUrl() method.
    }

    public function getCamera($code)
    {

    }

    public function devicePtzStart(string $code, $options)
    {
        // TODO: Implement devicePtzStart() method.
    }

    /**
     *
     * 关闭云台控制
     * @param string $code
     * @param $options
     * @return mixed
     */
    public function devicePtzStp(string $code, $options)
    {

    }

    /**
     * @return OpenYs7
     */
    protected function getYs7Sdk()
    {
        return $this->biz->offsetGet('sip.ys7_sdk');
    }

    public function getVideoRecorder($code)
    {
        // TODO: Implement getVideoRecorder() method.
    }

    public function stopLive($code, array $options = [])
    {
        // TODO: Implement stopLive() method.
    }
}