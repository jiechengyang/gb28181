<?php

namespace Biz\AkStreamSdk\HttpClient;


use Biz\AkStreamSdk\Toolkits\CurlHttpClient;
use Psr\Log\LoggerInterface;

class ZlmediaKitClient extends Client 
{
    private $secret;

    private $publicQueryParams = [];

    public function __construct(array $options)
    {
        $this->secret = $options['zlmediakit_secret'];
        isset($options['debug']) && $this->debug = $options['debug'];
        $this->apiUrl = $options['zlmediakit_api'];
        $this->publicQueryParams['secret'] = $this->secret;
        $this->createClient();
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
        $headers['Content-Type'] = 'application/json;charset=utf-8';
        $params = array_merge($this->publicQueryParams, $params);
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

    public function post($uri, $data = [], $params = [], $headers = [])
    {
        return null;
    }

        /**
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

}