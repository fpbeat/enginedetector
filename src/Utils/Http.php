<?php

namespace EngineDetector\Utils;

use EngineDetector\Http\Request;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\TransferStats;

class Http {
    /**
     * @param string $url
     * @param array $params
     *
     * @return bool
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function validateExisting($url, $uri, array $params = []) {
        $effectiveUri = NULL;

        try {
            $client = Request::factory(new CurlHandler())->create(array_merge([
                'attempts' => 0,
                'verify' => FALSE,
                'timeout' => 3,
                'on_stats' => function (TransferStats $stats) use (&$effectiveUri) {
                    $effectiveUri = $stats->getEffectiveUri();
                },
                'headers' => [
                    'User-Agent' => Request::getRandomUserAgent()
                ]
            ], $params));
            $response = $client->request('HEAD', $url, [
                'allow_redirects' => TRUE
            ]);

            if ($response->getStatusCode() === 200 && $effectiveUri instanceof \GuzzleHttp\Psr7\Uri) {
                return (bool)preg_match('/' . preg_quote($uri, '/') . '$/i', strval($effectiveUri));
            }

            return $response->getStatusCode() === 200;
        } catch (\RuntimeException $e) {
            return FALSE;
        }
    }
}