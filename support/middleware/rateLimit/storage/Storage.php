<?php

namespace support\middleware\rateLimit\storage;

interface Storage
{
    /**
     * @return bool
     */
    public function set($key, $value, $ttl);

    /**
     * @return bool
     */
    public function get($key);

    /**
     * @return bool
     */
    public function del($key);
}