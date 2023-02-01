<?php


namespace Biz\DataFilters\Resources;


use Biz\DataFilters\Filter;

class RecordFileFilter extends Filter
{
    protected $simpleFields = [
        'id',
        'main_id',
        'channel_id',
        'channel_name',
        'device_id',
        'start_time',
        'end_time',
        'duration',
        'file_size',
        'download_url',
        'record_date',
        'created_time',
    ];

    protected $publicFields = [
        'id',
        'main_id',
        'channel_id',
        'channel_name',
        'device_id',
        'start_time',
        'end_time',
        'duration',
        'file_size',
        'download_url',
        'record_date',
        'created_time',
    ];
    protected function simpleFields(&$data)
    {
        $this->commonFields($data);
    }

    protected function publicFields(&$data)
    {
        $this->commonFields($data);
    }

    private function commonFields(&$data)
    {
        $proxyURL = config('app.ak_config.record_file_proxy_url');
        if (!empty($data['download_url']) && $proxyURL) {
            $index = strpos($data['download_url'], '/record/');
            $subStr = substr($data['download_url'], $index + strlen('/record'));
            $data['download_url'] = rtrim($proxyURL) . $subStr;
        }
    }
}