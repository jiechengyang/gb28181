<?php

namespace process;

use Codeages\Biz\Framework\Context\Biz;
use support\bootstrap\BizInit;
use support\bootstrap\Container;


abstract class AbstractProcess
{
    /**
     * 
     * @return Biz
     */
    final protected function getBiz()
    {
        return BizInit::init();
//        return Container::get(Biz::class);
    }

}