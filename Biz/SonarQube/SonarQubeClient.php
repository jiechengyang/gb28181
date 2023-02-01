<?php

namespace Biz\SonarQube;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

class SonarQubeClient extends Client
{

    public function get($uri, array $options = []): ResponseInterface
    {
        $options = array_merge($options, [
            'auth' => [$this->getAuthToken(), '']
        ]);
        return parent::get($uri, $options);
    }

    private function getAuthToken()
    {
        return config('app.sonar_token');
    }
}
