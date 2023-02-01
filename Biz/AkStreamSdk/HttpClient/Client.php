<?php


namespace Biz\AkStreamSdk\HttpClient;


use Biz\AkStreamSdk\Toolkits\CurlHttpClient;
use Psr\Log\LoggerInterface;

class Client
{
    const SUCCESS_CODE = 1;

    const FAILED_CODE = -1;

    /**
     * @var int
     */
    protected $connectTimeout = 0.5;
    /**
     * @var int
     */
    protected $timeout = 0.5;
    /**
     * @var
     */
    protected $apiUrl;
    /**
     * @var string
     */
    protected $accessKey;
    /**
     * @var string
     */
    protected $accessSecret;
    /**
     * @var bool
     */
    protected $debug = false;
    /**
     * @var LoggerInterface|null
     */
    protected $logger = null;
    /**
     * @var CurlHttpClient
     */
    protected $client;

    /**
     * AKStream constructor.
     *
     * @param array('api_url' => 'xxx', 'access_key' => 'xxx', 'access_secret' => 'xxx', 'timeout' => 15, 'connect_timeout' => 15, 'api_version' => 'v1') $options
     */
    public function __construct(array $options)
    {
        $this->accessKey = $options['access_key'];
        isset($options['debug']) && $this->debug = $options['debug'];
        $this->apiUrl = $options['api_url'];
        isset($options['connect_timeout']) && $this->connectTimeout = intval($options['connect_timeout']);
        isset($options['timeout']) && $this->connectTimeout = intval($options['timeout']);
        $this->createClient();
    }


    protected function createClient()
    {
        $this->client = new CurlHttpClient();
        $this->client->setConfig([
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HEADER => true
        ]);

        $this->client->setConnectionTimeoutInMillis($this->connectTimeout * 1000);
        $this->client->setSocketTimeoutInMillis($this->timeout * 1000);
    }

    /**
     * @return int
     */
    public function getConnectTimeout()
    {
        return $this->connectTimeout;
    }


    /**
     * @return bool
     */
    public function isDebug()
    {
        return $this->debug;
    }

    /**
     * @return LoggerInterface|null
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface|null $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return string
     */
    public function getAccessKey()
    {
        return $this->accessKey;
    }


    /**
     * @return string
     */
    public function getAccessSecret()
    {
        return $this->accessSecret;
    }

    /**
     * @param int $connectTimeout
     */
    public function setConnectTimeout(int $connectTimeout): void
    {
        $this->connectTimeout = $connectTimeout;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * GET请求
     *
     * @param $uri
     * @param array $params
     * @param array $headers
     *
     * @return Response
     */
    public function get($uri, $params = [], $headers = [])
    {
        $url = $this->getRequestUrl($uri);
        $headers = $this->mergeHeader($headers);
        $this->log('Query Params:', $params);
        try {
            $rawResponse = $this->client->get($url, $params, $headers);
            list($rawHeaders, $rawBody) = $this->extractResponseHeadersAndBody($rawResponse);

            $response = new Response($rawHeaders, $rawBody);
            $this->log("[{$uri}] RESPONSE_BODY {$response->getBody()}", [], 'debug');

            return $response;
        } catch (\Exception $e) {
            $this->log("[$uri] GET ERROR", [
                'errCode' => $e->getCode(),
                'errInfo' => $e->getMessage(),
            ], 'error');
        }
    }

    /**
     * POST请求
     *
     * @param $uri
     * @param array post params    $data
     * @param array request params $params
     * @param array $headers
     *
     * @return Response
     */
    public function post($uri, $data = [], $params = [], $headers = [])
    {
        $url = $this->getRequestUrl($uri);
        $headers = $this->mergeHeader($headers);
        $headers['Content-Type'] = 'application/json;charset=utf-8';
        $this->log('Query Params:', $params);
        try {
            $json = is_array($data) ? json_encode($data) : $data;
            $rawResponse = $this->client->post($url, $json, $params, $headers);
            list($rawHeaders, $rawBody) = $this->extractResponseHeadersAndBody($rawResponse);
            $response = new Response($rawHeaders, $rawBody);
            $this->log('HTTP response.', [
                'statusCode' => $response->getHttpResponseCode(),
                'headers' => $response->getHeaders(),
                'body' => $response->getBody(),], $response->getHttpResponseCode() >= 400 ? 'error' : 'debug');

            return $response;
        } catch (\Exception $e) {
            $this->log("[$uri] POST ERROR", [
                'errCode' => $e->getCode(),
                'errInfo' => $e->getMessage(),
            ], 'error');
        }
    }

    /**
     * @param $uri
     *
     * @return string
     */
    public function getRequestUrl($uri)
    {
        $url = false !== strrpos($this->apiUrl, '/') ? $this->apiUrl . $uri : $this->apiUrl . '/' . $uri;
        $this->log('Request Url：' . $url, [], 'info');

        return $url;
    }

    /**
     * 合并http 头
     *
     * @param array $headers
     *
     * @return array
     */
    protected function mergeHeader($headers = [])
    {
        $headers['AccessKey'] = $this->accessKey;

        return $headers;
    }

    protected function log($msg, $content = [], $type = 'info')
    {
        if ($this->debug && $this->logger) {
            $this->logger->{$type}($msg, $content);
        }
    }


    protected function extractResponseHeadersAndBody($rawResponse)
    {
        $parts = explode("\r\n\r\n", $rawResponse['content']);
        $rawBody = array_pop($parts);
        $rawHeaders = implode("\r\n\r\n", $parts);

        return array(trim($rawHeaders), trim($rawBody));
    }
}