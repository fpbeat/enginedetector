<?php

namespace EngineDetector\Handler;

use EngineDetector\DetectResult;
use GuzzleHttp\Handler\CurlHandler;
use EngineDetector\Http\Request;

class WhatCms extends AbstractHandler {

    const HANDLER_NAME = 'WhatCMS';
    const ENDPOINT = 'https://whatcms.org/APIEndpoint/Detect';

    /**
     * @var \GuzzleHttp\Client $client
     */
    private $client;

    /**
     * @var string $apiKey
     */
    private $apiKey;

    /**
     * WhatCms constructor.
     *
     * @param string $apiKey
     */
    public function __construct($apiKey) {
        parent::__construct();

        $this->apiKey = $apiKey;
        $this->client = Request::factory(new CurlHandler())->create([
            'attempts' => 3,
            'verify' => FALSE
        ]);
    }

    /**
     * @param string $url
     * @param string $hostname
     *
     * @return DetectResult|mixed|null
     * @throws \Exception
     */
    public function detect($url, $hostname) {
        try {
            $request = $this->makeRequest($url);

            return $this->parse($request, $hostname);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new \Exception('Error calling WhatCMS API service');
        }
    }

    /**
     * @param array $response
     *
     * @return DetectResult|mixed|null
     * @throws \Exception
     */
    public function parse($response, $hostname) {
        $json = json_decode($response['content'], TRUE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(sprintf('Error parsing answer, stopped on: %s', json_last_error()));
        }

        if (intval($json['result']['code']) === 200) {
            return $this->setDetected([$hostname, $json['result']['name'], $json['result']['version'], $json['result']['confidence']]);
        }

        return NULL;
    }

    /**
     * @param array $params
     *
     * @return DetectResult|mixed
     */
    public function setDetected(array $params) {
        list($hostname, $name, $version, $confidence) = $params;

        return new DetectResult([
            'type' => NULL,
            'name' => $name,
            'version' => $version,
            'confidence' => $confidence,
            'handler' => self::HANDLER_NAME,
            'hostname' => $hostname
        ]);
    }

    /**
     * @param string $url
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function makeRequest($url) {
        $response = $this->client->request('GET', self::ENDPOINT, [
            'query' => [
                'url' => $url,
                'key' => $this->apiKey
            ]
        ]);

        $body = $response->getBody();
        $content = $body->getContents();

        return [
            'url' => $url,
            'content' => $content
        ];
    }
}