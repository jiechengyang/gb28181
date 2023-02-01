<?php


namespace  Biz\NotificationCenter;


use GuzzleHttp\Client;

class DingDingSender extends BaseSender implements SenderInterface
{

    /**
     * @param string|array $message ['link' => '*****', 'title' => '***']
     * @param array $params
     * @throws \Exception
     */
    public function sendMessage($message, $params = [])
    {
        // TODO: Implement sendMessage() method.
        if (!isset($params['access_token'])) {
            throw new \Exception("DingDing robot access_token is required");
        }

        if (!isset($params['secret'])) {
            throw new \Exception("DingDing robot secret must is required");
        }

        $accessToken = $params['access_token'];
        $secret = $params['secret'];
        $timestamp = time() * 1000;
        $signStr = $this->signStr($timestamp, $secret);
        $this->generateRequestData($message, $params);

        $response = $this->getDingDingHttpClient()->post('/robot/send', [
            'query' => ['access_token' => $accessToken, 'timestamp' => $timestamp, 'sign' => $signStr],
            'json' => $params
        ]);

        $this->afterSendInfo = json_decode($response->getBody()->getContents(), true);

    }

    private function generateRequestData($message, &$params)
    {
        unset($params['access_token'], $params['secret']);
        $msgType = $params['msgtype'];
        if ('text' === $msgType) {
            $params['text']['content'] = $message;
        } elseif ('link' === $msgType) {
            $params['link']['text'] = $message;
        } elseif ('markdown' === $msgType) {
            $params['markdown']['text'] = $message;
        }
    }

    private function signStr($timestamp, $secret)
    {
        //第一步，把timestamp+"\n"+密钥当做签名字符串，使用HmacSHA256算法计算签名，然后进行Base64 encode，最后再把签名参数再进行urlEncode，得到最终的签名（需要使用UTF-8字符集）
        $string = sprintf("%s\n%s", $timestamp, $secret);
        $hashCode = hash_hmac('sha256', $string, $secret, true);
        return base64_encode($hashCode);
    }

    /**
     * @return Client
     */
    protected function getDingDingHttpClient()
    {
        return new Client([
            'base_uri' => "https://oapi.dingtalk.com",
            'timeout' => 10.0,
            'connect_timeout' => 20
        ]);
    }

    public function getAfterSendInfo()
    {
        $afterSendInfo = $this->afterSendInfo;
        $info['message'] = $afterSendInfo['errmsg'];
        return $info;
    }
}