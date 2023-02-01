<?php


namespace Biz\SipClientSdk;


use Biz\SipGatewaySignature\SigntureHelper;
use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;
use support\utils\StringToolkit;

class SipHttpClient extends Client
{
    const APP_NAME = 'sip';

    const SIGNATURE_HEADERS = 'x-ca-key,x-ca-timestamp';

    private $debug = false;

    /**
     *
     * 请求超时的秒数。使用 0 无限期的等待(默认行为)
     * 单位：s
     *
     * @var int
     */
    private $timeout = 3;

    /**
     *
     * 等待服务器响应超时的最大值，使用 0 将无限等待 (默认行为)
     * 单位：s
     *
     * @var int
     */
    private $connectTimeout = 5;

    private $appKey;

    private $appSecret;

    /**
     * 不参与Headers签名的header key
     * @var string[]
     */
    protected $noSignatureHeaders = [
        'X-Ca-Signature',
        'X-Ca-Signature-Headers',
        'Accept',
        'Content-MD5',
        'Content-Type',
        'Date',
        'Content-Length',
        'Server',
        'Connection',
        'Host',
        'Transfer-Encoding',
        'X-Application-Context',
        'Content-Encoding',
    ];


    public function __construct(string $appKey, string $appSecret, array $config = [])
    {
        if (empty($config['base_uri'])) {
            throw new \Exception("base_uri is empty!");
        }

        if (strpos($config['base_uri'], self::APP_NAME) === false) {
            $config['base_uri'] = rtrim($config['base_uri'], '/') . '/' . self::APP_NAME;
        }

        $this->appKey = $appKey;
        $this->appSecret = $appSecret;
        $config['X-Ca-Key'] = $this->appKey;
        !isset($config['timeout']) && $config['timeout'] = $this->timeout;
        !isset($config['connect_timeout']) && $config['connect_timeout'] = $this->connectTimeout;
        $config['X-Ca-Signature-Headers'] = self::SIGNATURE_HEADERS;
        $config['headers'] = [
            'User-Agent' => 'SipGateway/1',
            'Accept' => '*/*',
            'Content-Type' => 'application/json;charset=utf-8',
            'X-Ca-Key' => $this->appKey
        ];
        parent::__construct($config);
    }

    public function get($uri, array $options = []): ResponseInterface
    {
        $uri = ltrim($uri, '/');
        if (empty($options['headers'])) {
            $options['headers'] = [];
        }

        return $this->sendContent('get', $uri, $options);
    }

    public function post($uri, array $options = []): ResponseInterface
    {
        $uri = ltrim($uri, '/');
        if (empty($options['headers'])) {
            $options['headers'] = [];
        }

        if (!empty($options['form_params'])) {
            $body = json_encode($options['form_params']);
            $options['headers']['Content-Md5'] = SigntureHelper::messageDigest($body);
        }

        return $this->sendContent('post', $uri, $options);
    }

    /**
     * @param $method
     * @param $uri
     * @param $options
     * @return ResponseInterface
     * @throws \Throwable
     */
    protected function sendContent($method, $uri, $options)
    {
        $options['headers'] = $this->mergeCommonHeaders($options['headers']);
        $options['headers']['X-Ca-Signature'] = $this->makeSignStr('GET', $this->combineUri($uri, $options['query'] ?? []), $options['headers']);
        try {
            if ($this->debug) {
                $this->getActionLogger()->write(sprintf("[%s] -------- before request start --------", $method));
                $this->getActionLogger()->write(json_encode([
                    'uri' => $uri,
                    'options' => $options
                ]));
                $this->getActionLogger()->write(sprintf("[%s] -------- before request end --------", $method), true);
            }

            /** @var $response ResponseInterface */
            $response = parent::$method($uri, $options);
            if ($this->debug) {
                $this->getActionLogger()->write(sprintf("[%s] -------- after response start --------", $method));
                $this->getActionLogger()->write(json_encode([
                    'statusCode' => $response->getStatusCode(),
                    'headers' => $response->getHeaders(),
                    'body' => $response->getBody()->getContents()
                ]));
                $this->getActionLogger()->write(sprintf("[%s] -------- after response end --------", $method), true);
                $response->getBody()->rewind();
            }

            return $response;
        } catch (\Throwable $e) {
            if ($this->debug) {
                $this->getActionLogger()->write(sprintf("[%s] -------- after request failed --------", $method));
                $this->getActionLogger()->write($e->getMessage(), true);
            }
            throw $e;
        }
    }

    protected function combineUri($uri, $queryParams)
    {
        return self::APP_NAME . '/' . $uri . '?' . $this->parseQueryParams($queryParams);
    }

    protected function parseQueryParams($queryParams)
    {
        if (is_array($queryParams)) {
            $queryParams = \http_build_query($queryParams, '', '&', \PHP_QUERY_RFC3986);
        }

        return $queryParams;
    }

    /**
     * @return ActionLogger|null
     */
    protected function getActionLogger()
    {
        $path = runtime_path() . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'sip-client';
        $logName = 'action' . date('Ymd') . '.log';

        return ActionLogger::getInstance($path, $logName);
    }

    protected function mergeCommonHeaders($headers)
    {
        $defaultHeaders = array_merge($this->getConfig('headers'), [
            'Date' => $this->getGmtTime(),
            'X-Ca-Timestamp' => (int)sprintf('%s000', time()),
            'X-Ca-Nonce' => StringToolkit::generateRandomString(),
        ]);

        return array_merge($headers, $defaultHeaders);
    }

    protected function getGmtTime()
    {
        return gmdate('D, d M Y H:i:s T');
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @param bool $debug
     */
    public function setDebug(bool $debug): void
    {
        $this->debug = $debug;
    }

    protected function makeSignStr($method, $uri, $headers)
    {
        $signArray = [strtoupper($method)];
        !empty($headers['Accept']) && $signArray[] = $headers['Accept'];
        !empty($headers['Content-Md5']) && $signArray[] = $headers['Content-Md5'];
        !empty($headers['Content-Type']) && $signArray[] = $headers['Content-Type'];
        !empty($headers['Date']) && $signArray[] = $headers['Date'];
        $signHeaderStr = $this->makeSignHeaderStr($headers);
        $signArray[] = $signHeaderStr;
        $uri = ltrim($uri, '/');
        $signArray[] = $uri;
        $sign = SigntureHelper::sign($signArray, $this->appSecret);

        return $sign['after'];
    }

    protected function makeSignHeaderStr($httpHeaders)
    {
        $needSignatureHeaders = explode(',', self::SIGNATURE_HEADERS);
        $needSignatureHeaders = SigntureHelper::getNeedSignatureHeaders($needSignatureHeaders);
        $headers = array_filter($httpHeaders, function ($value, $key) use ($needSignatureHeaders) {
            return !in_array($key, $this->noSignatureHeaders) && in_array($key, $needSignatureHeaders);
        }, ARRAY_FILTER_USE_BOTH);

        return SigntureHelper::getHeaderNormalizedString($headers);
    }
}