<?php

namespace app\admin;

use app\AbstractController;
use Biz\SystemLog\Service\SystemLogService;
use Biz\User\Service\UserService;
use support\exception\BadRequestHttpException;

class BaseController extends AbstractController
{
    /**
     *
     * 检查每个API必需参数的完整性
     * @param $requiredFields
     * @param $requestData
     * @return mixed
     */
    protected function checkRequiredFields($requiredFields, $requestData)
    {
        $requestFields = array_keys($requestData);
        foreach ($requiredFields as $field) {
            if (!in_array($field, $requestFields)) {
                throw new BadRequestHttpException("缺少必需的请求参数{$field}", null, self::ERROR_CODE_POST_DATA_FAILED);
            }
        }

        return $requestData;
    }
    /**
     * @return UserService
     */
    protected function getUserService()
    {
        return $this->createService('User:UserService');
    }

    /**
     * @return SystemLogService
     */
    protected function getLogService()
    {
        return $this->createService('SystemLog:SystemLogService');
    }
}