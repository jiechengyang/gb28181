<?php


namespace app\admin\controller;


use app\AbstractController;
use support\Request;
use support\bootstrap\Log;

class Talk extends AbstractController
{
    public function index(Request $request)
    {
        return json(['code' => self::BIS_SUCCESS_CODE, 'message' => 'Talk']);
    }

    public function pushMessage(Request $request, $auth_token)
    {
        $params = $request->all();
        $msgType = isset($params['msg_type']) ? $params['msg_type'] : 'text';
        if (true !== ($result = $this->validateParams($auth_token, $params, $msgType))) {
            return json([
                'code' => self::BIS_FAILED_CODE,
                'message' => $result
            ]);
        }

        $isAtAll = isset($params['at_all']) ? (bool)$params['at_all'] : false;
        $projectName = isset($params['project_name']) ? $params['project_name'] : '';
        $content = $params['message'];
        $mobiles = [];
        $todoStr = '';
        if (!empty($params['mobiles'])){
            $mobiles = explode(',', $params['mobiles']);
            $todoStr = $params['mobiles'];
        }

        $todoStr = $params['mobiles'];
        $clientIp = $request->getRealIp();
        $code = 0;
        $msg = 'ok';
        $isAtAllStr = $isAtAll ? "是" : "否";
        $printString = "项目：{$projectName}#客户端IP：{$clientIp},#收信人手机:{$todoStr}#消息类型:{$msgType}#是否@所有:{$isAtAllStr}#消息内容:{$content}";
        try {
            $dingdingNotification = $this->getDingDingNotification();
            $sendParams = [
                'msgtype' => $msgType,
                'access_token' => $params['robot_token'],
                'secret' => $params['robot_secret'],
                'at' => [
                    'atMobiles' => $mobiles,
                    'isAtAll' => $isAtAll
                ],
            ];
            if ('link' === $msgType) {
                $sendParams['link']['title'] = $params['link_title'];
                $sendParams['link']['messageUrl'] = $params['link_msg_url'];
                $sendParams['link']['picUrl'] = isset($params['link_pic_url']) ? $params['link_pic_url'] : '';
            }
            $dingdingNotification->send($content, $sendParams);
            $afterSendInfo = $dingdingNotification->getAfterSendInfo();
            $msg = $afterSendInfo['message'];
        } catch (\Exception $e) {
            $code = -1;
            $msg = $e->getMessage();
            Log::error("{$printString}----发送失败#错误信息:{$msg}");
        } finally {
            Log::info("{$printString}----发送成功#成功信息:{$msg}");
            return json(['code' => $code, 'message' => $msg]);
        }
    }

    /**
     * @param string $authToken
     * @param array $params
     * @param string $msgType
     * @return bool|string
     */
    protected function validateParams(string $authToken, array $params, $msgType = 'text')
    {
        try {
            $this->_commonValidate($authToken, $params);
            switch ($msgType) {
                case 'link':
                    $this->_linkValidate($params);
                    break;
                default:
                    break;
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return $e->getMessage();
        }
        return true;
    }

    private function _commonValidate($authToken, $params)
    {
        if ($authToken !== $this->getAllowAuthToken()) {
            $msg = '请求失败，auth_token不存在';
            throw new \Exception($msg);
        }

        if (empty($params['robot_token'])) {
            $msg = '请求失败，robot_token必须提供';
            throw new \Exception($msg);
        }

        if (empty($params['robot_secret'])) {
            $msg = '请求失败，robot_secret必须提供';
            throw new \Exception($msg);
        }

        if (empty($params['message'])) {
            $msg = '请求失败，message必须提供';
            throw new \Exception($msg);
        }

        // TODO: DingDing 接口不是必须，但我们这个是必须@到人
        if (!empty($params['mobiles'])) {
            $this->_validateAtMobiles($params['mobiles'], $params['message']);
        }

    }

    private function _validateAtMobiles($mobiles, $content = '')
    {
        $mobiles = explode(',', $mobiles);
        foreach ($mobiles as $mobile) {
            if (!$this->checkMobile($mobile)) {
                $msg = '请求失败，手机' . $mobile . '格式不正确';
                throw new \Exception($msg);
            }
        }

        $contentMobiles = $this->matchContentMobiles($content);
        !empty($contentMobiles) && $mobiles = array_intersect($mobiles, $contentMobiles);
        if (empty($mobiles)) {
            $msg = '请求失败，请查看message里面的手机号是否存在于mobiles';
            throw new \Exception($msg);
        }
    }

    private function _linkValidate($params)
    {
        if (empty($params['link_title'])) {
            $msg = '请求失败，消息标题 link_title 必须提供';
            throw new \Exception($msg);
        }

        if (empty($params['link_msg_url'])) {
            $msg = '请求失败，消息跳转的URL link_msg_url 必须提供';
            throw new \Exception($msg);
        }
    }

    /**
     * @return array
     */
    protected function getAllowAuthToken()
    {
        return config('app.auth_token');
    }


    /**
     * @param $mobile
     * @return bool
     */
    protected function checkMobile($mobile)
    {
        return preg_match("/^1[345789]\d{9}$/", $mobile);
    }

    protected function matchContentMobiles($content)
    {
        preg_match_all("/@(1\d{10})+/i", $content, $mobiles);

        return isset($mobiles[1]) ? $mobiles[1] : [];
    }

    protected function addLog($content, $todoStr, $serverIp, $clientIp, $responseMsg = '', $projectName = '')
    {
        // $sendLog = new SendLog();
        // $sendLog->project_name = $projectName;
        // $sendLog->todo_str = $todoStr;
        // $sendLog->content = $content;
        // $sendLog->server_ip = $serverIp;
        // $sendLog->client_ip = $clientIp;
        // $sendLog->response_msg = $responseMsg;
        // $sendLog->created_time = time();
        // $sendLog->save();
    }

    protected function getDingDingNotification()
    {
        return $this->getBiz()['notification.dingding'];
    }
}