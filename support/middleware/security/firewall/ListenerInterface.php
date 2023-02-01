<?php


namespace support\middleware\security\firewall;


use Webman\Http\Request;

interface ListenerInterface
{
    public function handle(Request $request);
}