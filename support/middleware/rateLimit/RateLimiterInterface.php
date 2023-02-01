<?php

namespace support\middleware\rateLimit;


use support\Request;

interface RateLimiterInterface
{
    const PASS = 30000;

    const CAPTCHA_OCCUR = 30001;

    const MAX_REQUEST_OCCUR = 30002;

    const MAX_REQUEST_MSG_KEY = 'request.max_attempt_reach';

    const CAPTCHA_OCCUR_MSG_KEY = 'request.need_verify_captcha';

    public function handle(Request $request);
}