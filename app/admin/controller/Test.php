<?php

namespace app\admin\controller;

use app\AbstractController;

class Test  extends AbstractController
{

    public function index()
    {
        $this->getEmailNotification()->send('这是一封测试邮件');
        return json(['code' => self::BIS_SUCCESS_CODE, 'message' => 'Test']);
    }

    protected function getEmailNotification()
    {
        return $this->getBiz()['notification.email'];
    }
}
