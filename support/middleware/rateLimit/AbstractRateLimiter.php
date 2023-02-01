<?php


namespace support\middleware\rateLimit;


use support\middleware\rateLimit\RateLimitException;
use Codeages\Biz\Framework\Context\Biz;

class AbstractRateLimiter
{
    /**
     * @var Biz
     */
    protected $biz;

    public function __construct(Biz $biz)
    {
        $this->biz = $biz;
    }

    public function setBiz(Biz $biz)
    {
        $this->biz = $biz;
    }

    protected function createMaxRequestOccurException()
    {
        return RateLimitException::FORBIDDEN_MAX_REQUEST();
    }

    protected function createEmailMaxRequestOccurException()
    {
        return RateLimitException::FORBIDDEN_EMAIL_MAX_REQUEST();
    }

    protected function createUgcReportMaxRequestOccurException()
    {
        return RateLimitException::FORBIDDEN_UGC_REPORT_MAX_REQUEST();
    }
}