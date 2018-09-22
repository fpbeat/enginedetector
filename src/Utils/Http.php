<?php

namespace EngineDetector\Utils;

use EngineDetector\Http\Request;
use GuzzleHttp\Handler\CurlHandler;

class Http {
    /**
     * @param string $url
     * @param array $params
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function validateExisting($url, array $params = []) {
        try {
            $client = Request::factory(new CurlHandler())->create(array_merge([
                'attempts' => 0,
                'verify' => FALSE,
                'timeout' => 3,
                'headers' => [
                    'User-Agent' => Request::getRandomUserAgent()
                ]
            ], $params));
            $response = $client->request('HEAD', $url, [
                'allow_redirects' => TRUE
            ]);

            return $response->getStatusCode() === 200;
        } catch (\RuntimeException $e) {
            return FALSE;
        }
    }
}