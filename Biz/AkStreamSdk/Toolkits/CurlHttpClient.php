<?php


namespace Biz\AkStreamSdk\Toolkits;

use Exception;

class CurlHttpClient
{
    /**
     * @var array
     */
    protected $headers = [];

    /**
     * @var int
     */
    private $connectTimeout = 5000;

    /**
     * @var int
     */
    protected $socketTimeout = 5000;

    /**
     * @var array curl config
     */
    protected $config = [];

    /**
     * HttpClient
     *
     * @param array $headers HTTP header
     */
    public function __construct($headers = [])
    {
        $this->headers = $this->buildHeaders($headers);
    }

    /**
     * 连接超时
     *
     * @param int $ms 毫秒
     */
    public function setConnectionTimeoutInMillis($ms)
    {
        $this->connectTimeout = $ms;
    }

    /**
     * 响应超时
     *
     * @param int $ms 毫秒
     */
    public function setSocketTimeoutInMillis($ms)
    {
        $this->socketTimeout = $ms;
    }

    /**
     * 配置
     *
     * @param array $config
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * 请求预处理
     *
     * @param resource $ch
     */
    public function prepare($ch)
    {
        foreach ($this->config as $key => $value) {
            curl_setopt($ch, $key, $value);
        }
    }

    /**
     * @param string $url
     * @param array $data HTTP POST BODY
     * @param array $param HTTP URL
     * @param array $headers HTTP header
     *
     * @return array
     */
    public function post($url, $data = [], $params = [], $headers = [])
    {
        $url = $this->buildUrl($url, $params);
        $headers = array_merge($this->headers, $this->buildHeaders($headers));

        $ch = curl_init();
        $this->prepare($ch);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        if (!isset($this->config[CURLOPT_HEADER])) {
            curl_setopt($ch, CURLOPT_HEADER, false);
        }

        if (!isset($this->config[CURLOPT_RETURNTRANSFER])) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }

        if (!isset($this->config[CURLOPT_SSL_VERIFYPEER])) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->socketTimeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->connectTimeout);
        $content = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (0 === $code) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        return [
            'code' => $code,
            'content' => $content,
        ];
    }

    /**
     * @param string $url
     * @param array $datas HTTP POST BODY
     * @param array $param HTTP URL
     * @param array $headers HTTP header
     *
     * @return array
     */
    public function multiPost($url, $datas = [], $params = [], $headers = [])
    {
        $url = $this->buildUrl($url, $params);
        $headers = array_merge($this->headers, $this->buildHeaders($headers));

        $chs = [];
        $result = [];
        $mh = curl_multi_init();
        foreach ($datas as $data) {
            $ch = curl_init();
            $chs[] = $ch;
            $this->prepare($ch);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($data) ? http_build_query($data) : $data);
            curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->socketTimeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->connectTimeout);
            curl_multi_add_handle($mh, $ch);
        }

        $running = null;
        do {
            curl_multi_exec($mh, $running);
            usleep(100);
        } while ($running);

        foreach ($chs as $ch) {
            $content = curl_multi_getcontent($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $result[] = [
                'code' => $code,
                'content' => $content,
            ];
            curl_multi_remove_handle($mh, $ch);
        }
        curl_multi_close($mh);

        return $result;
    }

    /**
     * @param string $url
     * @param array $param HTTP URL
     * @param array $headers HTTP header
     *
     * @return array
     */
    public function get($url, $params = [], $headers = [])
    {
        $url = $this->buildUrl($url, $params);
        $headers = array_merge($this->headers, $this->buildHeaders($headers));

        $ch = curl_init();
        $this->prepare($ch);
        curl_setopt($ch, CURLOPT_URL, $url);
        if (!isset($this->config[CURLOPT_HEADER])) {
            curl_setopt($ch, CURLOPT_HEADER, false);
        }

        if (!isset($this->config[CURLOPT_RETURNTRANSFER])) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        }

        if (!isset($this->config[CURLOPT_SSL_VERIFYPEER])) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, $this->socketTimeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, $this->connectTimeout);
        $content = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (0 === $code) {
            throw new Exception(curl_error($ch));
        }

        curl_close($ch);

        return [
            'code' => $code,
            'content' => $content
        ];
    }

    /**
     * 构造 header
     *
     * @param array $headers
     *
     * @return array
     */
    private function buildHeaders($headers)
    {
        $result = [];
        foreach ($headers as $k => $v) {
            $result[] = sprintf('%s:%s', $k, $v);
        }

        return $result;
    }

    /**
     * @param string $url
     * @param array $params 参数
     *
     * @return string
     */
    private function buildUrl($url, $params)
    {
        if (!empty($params)) {
            $str = http_build_query($params);

            return $url . (false === strpos($url, '?') ? '?' : '&') . $str;
        }

        return $url;
    }
}