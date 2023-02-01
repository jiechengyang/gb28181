<?php

function curlRequest($remote_server, $post_string)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $remote_server);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json;charset=utf-8'));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // 线下环境不用开启curl证书验证, 未调通情况可尝试添加该代码
     curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
     curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $data = curl_exec($ch);
    curl_close($ch);
    return $data;
}

function signStr($timestamp, $secret)
{
    //第一步，把timestamp+"\n"+密钥当做签名字符串，使用HmacSHA256算法计算签名，然后进行Base64 encode，最后再把签名参数再进行urlEncode，得到最终的签名（需要使用UTF-8字符集）
    $string = sprintf("%s\n%s", $timestamp, $secret);
    $hashCode = hash_hmac('sha256', $string, $secret, true);
    return base64_encode($hashCode);
}

$timestamp = time() * 1000;
$webhook = "https://oapi.dingtalk.com/robot/send?access_token=e762a30882939ad91d55c36a62bbfde1ebfd77080c09edc35e3503f25246d26e";
//YangBoom:dc4d2d29aabce4c006eb8e163e537df6564fddbf80af0a7993b2a2f8c392b3ef
//YangBoom:SEC6d1dca0f5d992f584d531e98ad8857204e516ea598bfa54492b908fdeb7f0400
$signStr = signStr($timestamp, "SECbe963bc9dd3600edeb38a73a07486e556b0dfb92222fd786aa9c74ea9197c037");
$webhook .= "&timestamp={$timestamp}&sign={$signStr}";
//$message = "素玉老师，乘风破浪";
$message = file_get_contents('test.md') . "\n";
//$data = array('msgtype' => 'text', 'text' => array('content' => $message));
$data = array('msgtype' => 'markdown', 'markdown' => array(
    'title' => '启迪项目代码分析结果',
    'text' => $message
));
$data_string = json_encode($data);
var_dump($webhook);
$result = curlRequest($webhook, $data_string);
var_dump($result);