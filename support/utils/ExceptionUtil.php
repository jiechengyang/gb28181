<?php


namespace support\utils;


use Codeages\Biz\Framework\Service\Exception\NotFoundException;
use Psr\Container\NotFoundExceptionInterface;
use support\exception\BadRequestHttpException;
use support\exception\HttpExceptionInterface;

class ExceptionUtil
{
    public static function getErrorAndHttpCodeFromException($exception)
    {
        $error = [];
        if (self::checkIsBusinessException($exception)) {
            $error['message'] = $exception->getMessage();
            $error['code'] = $exception->getCode();
            $httpCode = $exception->getStatusCode();
        } elseif ($exception instanceof NotFoundExceptionInterface || $exception instanceof NotFoundException) {
            $error['message'] = 'Not Found';
            $error['code'] = $exception->getCode() ?: -1;
            $httpCode = 404;
        } elseif ($exception instanceof BadRequestHttpException) {
            $error['message'] = $exception->getMessage();
            $error['code'] = $exception->getCode() ?: -1;
            $httpCode = 400;
        } else {
            $error['message'] = 'Internal server error';
            $error['code'] = $exception->getCode() ?: -1;
            $httpCode = 500;
        }

        return [$httpCode, $error];
    }

    private static function checkIsBusinessException($exception)
    {
        if ($exception instanceof HttpExceptionInterface) {
            return true;
        }

        return false;
    }
}