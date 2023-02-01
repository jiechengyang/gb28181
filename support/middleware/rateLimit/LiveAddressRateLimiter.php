<?php


namespace support\middleware\rateLimit;


use support\Request;

class LiveAddressRateLimiter extends AbstractRateLimiter implements RateLimiterInterface
{
    /**
     * @var RateLimiter
     */
    protected $ipHourRateLimiter;

    /**
     * @var RateLimiter
     */
    protected $siteDayRateLimiter;

    const IP_MAX_ALLOW_ATTEMPT_ONE_HOUR = 30;

    const SITE_MAX_ALLOW_ATTEMPT_ONE_DAY = 5000;

    public function handle(Request $request)
    {
        $ihr = $this->ipHourRateLimiter->check($request->getRealIp());
        $sdr = $this->siteDayRateLimiter->check('site');

        $isLimitReach = $ihr <= 0 || $sdr <= 0;
        if ($isLimitReach) {
            throw $this->createMaxRequestOccurException();
        }
    }
}