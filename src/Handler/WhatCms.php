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
    private $apiKey;

    public function __construct($apiKey) {
        parent::__construct();

        $this->apiKey = $apiKey;
        $this->client = Request::factory(new CurlHandler())->create([
            'attempts' => 3,
            'verify' => FALSE
        ]);
    }

    public function detect($url) {
        try {
            $request = $this->makeRequest($url);

            return $this->parse($request);
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new \Exception('Error calling WhatCMS API service');
        }
    }

    public function parse($response) {
        $json = json_decode($response['content'], TRUE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(sprintf('Error parsing answer, stopped on: %s', json_last_error()));
        }

        if (intval($json['result']['code']) === 200) {
            return $this->setDetected([$json['result']['name'], $json['result']['version'], $json['result']['confidence']]);
        }

        return NULL;
    }

    public function setDetected(array $params) {
        list($name, $version, $confidence) = $params;

        return new DetectResult([
            'type' => NULL,
            'engine' => $name,
            'version' => $version,
            'confidence' => $confidence,
            'handler' => self::HANDLER_NAME
        ]);
    }

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