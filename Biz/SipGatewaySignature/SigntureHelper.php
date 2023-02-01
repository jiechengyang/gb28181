<?php


namespace Biz\SipGatewaySignature;


class SigntureHelper
{
    public static function getNeedSignatureHeaders($headers)
    {
        return array_map(function ($value) {
            return self::keyFormat($value);
        }, $headers);
    }

    public static function keyFormat($key)
    {
        return str_replace(' ', '-', ucwords(str_replace('-', ' ', $key)));
    }

    public static function getHeaderNormalizedString(array $params, $keyIsLower = true)
    {
        return self::generateNormalizedString($params, $keyIsLower);
    }

    public static function getParamsNormalizedString(array $params, $keyIsLower = false)
    {
        return empty($params) ? '' : self::generateNormalizedString($params, $keyIsLower, '=', '&');
    }

    private static function generateNormalizedString($params, $keyIsLower = false, $splitStr1 = ':', $splitStr2 = "\n")
    {
        ksort($params);
        $normalized = [];
        array_walk($params, function ($val, $key) use (&$normalized, $splitStr1, $keyIsLower) {
            $keyIsLower && $key = strtolower($key);
            $normalized[] = $key . $splitStr1 . $val;
        });

        return implode($splitStr2, $normalized);
    }

    public static function messageDigest($body)
    {
        return base64_encode(md5($body, true));
    }

    public static function sign($params, $secret)
    {
        ksort($params);
        $str = implode("\n", $params);

        return [
            'before' => $str,
            'after' => base64_encode(hash_hmac('sha256', $str, $secret, true))
        ];
    }
}