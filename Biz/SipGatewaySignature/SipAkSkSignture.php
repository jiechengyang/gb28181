<?php


namespace Biz\SipGatewaySignature;


use Biz\ThirdParty\Exception\ThirdPartyException;
use Biz\ThirdParty\Service\ThirdPartyService;
use Codeages\Biz\Framework\Context\Biz;
use support\exception\HttpException;
use support\Request;
use support\Singleton;

/**
 *
 *
 * 【必选】X-Ca-Key：appKey。
 * 【必选】X-Ca-Signature：签名。
 * 【必选】X-Ca-Signature-Headers：参与headers签名计算的header的key转换为小写字母，按照字典排序后多个key之间使用英文逗号分割，组成字符串。
 * 【可选】X-Ca-Timestamp：API 调用者传递时间戳，值为当前时间的毫秒数，即从1970年1月1日起至今的时间转换为毫秒。
 * 【可选】X-Ca-Nonce：API 调用者生成的 32随机字符串。
 * HmacSHA256
 */
class SipAkSkSignture
{
    use Singleton;

    protected $biz = null;

    private $appKey;

    private $appSecret;

    private $httpMethod;

    private $accept = null;

    private $contentMd5 = null;

    private $contentType = null;

    private $date = null;

    private $timestamp = null;

    private $none = null;

    private $httpHeaders = [];

    /**
     *
     * 参与headers签名计算的header的key转换为小写字母，按照字典排序后多个key之间使用英文逗号分割，组成字符串
     * 默认值x-ca-key,x-ca-timestamp
     * @var string
     */
    private $needSignatureHeaders = 'x-ca-key,x-ca-timestamp';

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

    /**
     * @var string
     * Url(指path+query+bodyForm)组成
     */
    private $url;

    private $timeOut = 300;

    public function __construct(Biz $biz)
    {
        $this->biz = $biz;
    }

    public function __destruct()
    {
        $this->biz = null;
    }

    public function check(Request $request)
    {
        $appKey = $request->header('x-ca-key');
        if (empty($appKey) || 18 !== strlen($appKey)) {
            throw ThirdPartyException::FAILED_APP_KEY();
        }

        $requestLiveProvider = $request->header('x-live-provider');
        $thirdParty = $this->validateThirdParty($this->getThirdPartyService()->getThirdPartyByAppKey($appKey));
        $this->validateLiveProvider($thirdParty, $requestLiveProvider);
        $noSign = $request->header('x-no-sign');
        if ('yes' === $noSign) {
            $this->getRedis()->set(sprintf("partner:%s", $appKey), serialize($thirdParty));
            return $thirdParty;
        }

        $this->needSignatureHeaders = $this->getNeedSignatureHeaders($request->header('X-Ca-Signature-Headers', $this->needSignatureHeaders));
        $this->accept = $request->header('Accept');
        $this->contentType = $request->header('Content-Type');
        $this->date = $request->header('Date');
        $this->timestamp = $request->header('X-Ca-Timestamp');
        $this->httpMethod = strtoupper($request->method());
        $this->httpHeaders = $request->header();
        $this->none = $request->header('X-Ca-None');
        if ($this->getIsTimeOut($this->timestamp)) {
            throw new HttpException(403, '请求超时', null, [], 4031101);
        }

        if (!empty($this->none) && 32 !== strlen($this->none)) {
            throw new HttpException(403, 'UUID必须是32位随机字符串', null, [], 4031102);
        }

        $this->appKey = $appKey;
        $this->appSecret = $thirdParty['partner_sceret'];
        if ('POST' === $this->httpMethod) {
            $this->contentMd5 = $request->header('Content-MD5');
            $postData = $request->post();
            if (is_array($postData)) {
                $postData = json_encode($postData);//中文是否转码
            }

            $contentMd5 = SigntureHelper::messageDigest($postData);
            if ($contentMd5 !== $this->contentMd5) {
                throw new HttpException(403, '信息摘要错误，请检查body内容', null, [], 4031103);
            }
        }

        $this->url = $request->uri();
        $sign = $this->makeSign();
        $clientSignatureStr = $request->header('X-Ca-Signature');
        if ($sign['after'] !== $clientSignatureStr) {
//            throw new HttpException(403, '签名错误: ' . $sign['after'] . '; str=' . $sign['before'], null, [], 4031104);
            throw new HttpException(403, '签名错误', null, [], 4031104);
        }
        $this->getRedis()->set(sprintf("partner:%s", $appKey), serialize($thirdParty));
        return $thirdParty;
    }

    protected function validateThirdParty($thirdParty)
    {
        if (empty($thirdParty)) {
            throw ThirdPartyException::NOTFOUND_PARTNER();
        }

        if ($thirdParty['locked'] == 1) {
            throw ThirdPartyException::LOCKED_PARTNER();
        }

        if ($thirdParty['expired_time'] > 0 && time() > $thirdParty['expired_time']) {
            throw ThirdPartyException::EXPIRED_PARTNER();
        }

        return $thirdParty;
    }

    protected function validateLiveProvider($thirdParty, $requestLiveProvider)
    {
        if (!in_array($requestLiveProvider, $thirdParty['live_providers'])) {
            throw ThirdPartyException::LIVE_PROVINDER_NOT_EXIST();
        }
    }

    /**
     * HTTP METHOD + "\n" +
     * Accept + "\n" +     //建议显示设置 Accept Header，部分 Http 客户端当 Accept 为空时会给 Accept
     * 设置默认值：，导致签名校验失败。
     * Content-MD5  + "\n" +
     * Content-Type + "\n" +
     * Date + "\n" +
     * Headers +
     * Url
     * HTTPMethod为全大写，如 “POST”。如果请求headers中不存在Accept、Content-MD5、Content-Type、Date 则不需要添加换行符”\n”。
     * 签名字符串由Http Method、headers、Url(指path+query+bodyForm)组成。以AppSecret为密钥，使用HmacSHA256算法对签名字符串生成消息摘要，对消息摘要使用BASE64算法生成签名（签名过程中的编码方式全为UTF-8）
     */
    protected function makeSign()
    {
        $signArray = [$this->httpMethod];
        $this->accept !== null && $signArray[] = $this->accept;
        !empty($this->contentMd5) && $signArray[] = $this->contentMd5;
        !empty($this->contentType) && $signArray[] = $this->contentType;
        !empty($this->date) && $signArray[] = $this->date;
        $signHeaderStr = $this->makeSignHeaderStr($this->httpHeaders);
        $signArray[] = $signHeaderStr;
        $uri = ltrim($this->url, '/');
        $signArray[] = $uri;

        return SigntureHelper::sign($signArray, $this->appSecret);
    }

    private function makeSignHeaderStr($httpHeaders)
    {
        $needSignatureHeaders = explode(',', $this->needSignatureHeaders);
        $needSignatureHeaders = SigntureHelper::getNeedSignatureHeaders($needSignatureHeaders);
        $headers = array_filter($httpHeaders, function ($value, $key) use ($needSignatureHeaders) {
            $key = SigntureHelper::keyFormat($key);
            return !in_array($key, $this->noSignatureHeaders) && in_array($key, $needSignatureHeaders);
        }, ARRAY_FILTER_USE_BOTH);


        return SigntureHelper::getHeaderNormalizedString($headers);
    }

    private function getNeedSignatureHeaders($xCaSignatureHeaders)
    {
        $xCaSignatureHeaders = explode(',', $xCaSignatureHeaders);
        ksort($xCaSignatureHeaders);

        return implode(',', $xCaSignatureHeaders);
    }

    private function getUtcTime()
    {
        date_default_timezone_set('UTC');
        $timestamp = new \DateTime();
        $timeStr = $timestamp->format("Y-m-d\TH:i:s\Z");
        return $timeStr;
    }

    protected function getIsTimeOut($time)
    {
        return abs(time() * 1000 - (int)$time) > $this->timeOut * 1000;
    }

    /**
     * @return ThirdPartyService
     * @throws \Webman\Exception\NotFoundException
     */
    protected function getThirdPartyService()
    {
        return $this->biz->service('ThirdParty:ThirdPartyService');
    }

    /**
     * @return \support\bootstrap\Redis
     */
    protected function getRedis()
    {
        return $this->biz->offsetGet('redis.api.cache');
    }
}