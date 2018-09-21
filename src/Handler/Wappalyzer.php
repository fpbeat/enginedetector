<?php

namespace EngineDetector\Handler;

use EngineDetector\DetectResult;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Cookie\CookieJar;
use EngineDetector\Http\Request;

class Wappalyzer extends AbstractHandler {

    const HANDLER_NAME = 'Wappalyzer';

    /**
     * @var \GuzzleHttp\Client $client
     */
    private $client;

    /**
     * Wappalyzer constructor.
     */
    public function __construct() {
        parent::__construct();

        $this->client = Request::factory(new CurlHandler())->create([
            'attempts' => $this->config->get('request_attempts', 3),
            'verify' => FALSE,
            'headers' => [
                'User-Agent' => Request::getRandomUserAgent()
            ]
        ]);
    }

    /**
     * @param string $url
     *
     * @return DetectResult|null
     * @throws \EngineDetector\Exception\InvalidUrlException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \ReflectionException
     */
    public function detect($url) {
        $request = $this->makeRequest($url);

        $class = new \ReflectionClass('\\EngineDetector\\Handler\\Wappalyzer\\Parser');
        $parser = $class->newInstanceArgs($request);

        foreach ($this->loadSignatures() as $name => $signature) {
            foreach ($signature as $type => $rule) {
                if (call_user_func([$parser, sprintf('is%sMatch', ucfirst($type))], $rule)) {
                    list($pattern, $value) = $parser->getSuccessMatch();

                    return $this->setDetected([$name, $type, $pattern, $value]);
                }
            }
        }

        return NULL;
    }

    /**
     * @param string $engine
     * @param string $type
     * @param array $pattern
     * @param array $value
     *
     * @return DetectResult
     */
    public function setDetected(array $params) {
        list($engine, $type, $pattern, $value) = $params;

        $app = ['type' => $type, 'engine' => $engine, 'version' => NULL, 'confidence' => 100, 'handler' => __CLASS__];

        // Set confidence level
        if (isset($pattern['confidence'])) {
            $app['confidence'] = $pattern['confidence'];
        }

        // Detect version number
        if (isset($pattern['version'])) {
            if (preg_match('/^(\\\\)?([a-z0-9]+)(?:\?(.+))?$/i', $pattern['version'], $versionMatch)) {
                if ($versionMatch[1] === '\\') {
                    $version = trim($value[intval($versionMatch[2])]);

                    $app['version'] = isset($version) && !empty($version) ? $version : $versionMatch[3];
                } else {
                    $app['version'] = $versionMatch[2];
                }
            }
        }

        return new DetectResult($app);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function loadSignatures() {
        $signatures = file_get_contents($this->config->get('signatures_path'));
        $json = json_decode($signatures, TRUE);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(sprintf('Error parsing signatures, stopped on: %s', json_last_error()));
        }

        return $json;
    }

    /**
     * @param string $url
     *
     * @return array
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function makeRequest($url) {
        $cookieJar = new CookieJar();

        $response = $this->client->request('GET', $url, [
            'allow_redirects' => TRUE,
            'cookies' => $cookieJar
        ]);

        $body = $response->getBody();
        $content = $body->getContents();

        return [
            'url' => $url,
            'content' => $content,
            'headers' => $response->getHeaders(),
            'cookies' => $cookieJar->toArray()
        ];
    }
}