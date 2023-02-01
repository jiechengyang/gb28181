<?php


namespace Biz\AkStreamSdk\Resources;


use Biz\AkStreamSdk\HttpClient\ZlmediaKitClient;
use Biz\AkStreamSdk\HttpClient\Response;

abstract class AbstractZlmResource
{
    /**
     * @var ZlmediaKitClient
     */
    protected $client;

    protected $errorCodes = [
        0 => '成功',
        -400 => '代码异常',
        -300 => '参数不合法',
        -200 => 'sql执行失败',
        -100 => '鉴权失败',
        -1 => '业务代码执行失败' 
    ];

    public function __construct(ZlmediaKitClient $client)
    {
        $this->client = $client;
    }

    public function __destruct()
    {
        $this->client = null;
    }

    /**
     * @return $this
     */
    public function configure()
    {
        return $this;
    }

    /**
     * @param $params
     * @param array $necessary
     */
    protected function checkParamIsSet($params, array $necessary)
    {
        for ($i = 0; $i < count($necessary); ++$i) {
            if (!array_key_exists($necessary[$i], $params)) {
                throw new \InvalidArgumentException('参数' . $necessary[$i] . '必须传递');
            }
        }
    }

    /**
     * @param $uri
     * @param array $params
     * @return array|null[]|string|null
     */
    protected function clientGet($uri, array $params = [])
    {
        return $this->responseFormat($this->client->get($uri, $params));
    }

    /**
     * @param $uri
     * @param array $data
     * @param array $params
     * @return array|null[]|string|null
     */
    protected function clientPost($uri, $data = [], $params = [])
    {
        $response = $this->client->post($uri, $data, $params);

        return $this->responseFormat($response, 'POST');
    }

    /**
     * @param Response $response
     * @param string $method
     * @return array|string|null
     */
    protected function responseFormat(?Response $response, $method = 'GET')
    {
        if (!$response instanceof Response) {
            return [-1, null, '其它错误'];
        }

        if (200 !== $response->getHttpResponseCode()) {
            return  [-1, null, '其它错误'];
        }

        $headers = $response->getHeaders();
        if (isset($headers['Content-Type']) &&
            (
                false !== strrpos($headers['Content-Type'], "application/json") ||
                false !== strrpos($headers['Content-Type'], "text/json"))
        ) {
            $data = json_decode($response->getBody(), true);
            $msg = $this->errorCodes[$data['code']] ?? '';
            $msg .= '：' . ($data['msg'] ?? '');
            
            return [$data['code'], $data['data'] ?? null, trim($msg, '：')];
        }

    }
}