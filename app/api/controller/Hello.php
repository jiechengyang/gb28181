<?php

namespace app\api\controller;

class Hello extends \app\AbstractController
{
    public function test()
    {
        return $this->createSuccessJsonResponse(null, 'Hello World');
    }
}