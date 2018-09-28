<?php

namespace EngineDetector\Handler;

use EngineDetector\DetectResult;
use EngineDetector\Utils\Http;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Cookie\CookieJar;
use EngineDetector\Http\Request;
use GuzzleHttp\TransferStats;

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
     * @param string $hostname
     *
     * @return DetectResult|null
     * @throws \EngineDetector\Exception\InvalidUrlException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \ReflectionException
     */
    public function detect($url, $hostname) {
        $request = $this->makeRequest($url);

        $class = new \ReflectionClass('\\EngineDetector\\Handler\\Wappalyzer\\Parser');
        $parser = $class->newInstanceArgs($request);

        foreach ($this->loadSignatures() as $name => $signature) {
            foreach ($signature as $type => $rule) {
                switch ($type) {
                    case 'headers':
                    case 'html':
                    case 'url':
                    case 'meta':
                    case 'script':
                    case 'cookies':
                        if (call_user_func([$parser, sprintf('is%sMatch', ucfirst($type))], $rule)) {
                            list($pattern, $value) = $parser->getSuccessMatch();

                            return $this->setDetected([$hostname, $name, $type, $pattern, $value]);
                        }
                        break;
                    case 'link':
                        if ($parser->isLinkMatch($rule, function ($link) use ($request, $hostname) {
                            $url = sprintf('%s://%s/%s', $request['scheme'] ?: 'http', $hostname, ltrim($link, '/'));

                            return Http::validateExisting($url, $link);
                        })) {
                            list($pattern, $value) = $parser->getSuccessMatch();

                            return $this->setDetected([$hostname, $name, $type, $pattern, $value]);
                        }
                        break;
                }
            }
        }

        return NULL;
    }

    /**
     * @param array $params
     *
     * @return DetectResult
     */
    public function setDetected(array $params) {
        list($hostname, $engine, $type, $pattern, $value) = $params;

        $app = ['type' => $type, 'name' => $engine, 'version' => NULL, 'confidence' => 100, 'handler' => self::HANDLER_NAME, 'hostname' => $hostname];

        // Set confidence level
        if (isset($pattern['confidence'])) {
            $app['confidence'] = $pattern['confidence'];
        }

        // Detect version number
        if (isset($pattern['version'])) {
            if (preg_match('/^(\\\\)?([a-z0-9]+)(?:\?(.+))?$/i', $pattern['version'], $versionMatch)) {
                if ($versionMatch[1] === '\\') {
                    $version = isset($value[intval($versionMatch[2])]) ? trim($value[intval($versionMatch[2])]) : NULL;

                    if (!empty($version)) {
                        $app['version'] = $version;
                    } elseif (isset($versionMatch[3])) {
                        $app['version'] = $versionMatch[3];
                    }
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
        $effectiveUri = NULL;
        $cookieJar = new CookieJar();

        $response = $this->client->request('GET', $url, [
            'allow_redirects' => TRUE,
            'cookies' => $cookieJar,
            'on_stats' => function (TransferStats $stats) use (&$effectiveUri) {
                $effectiveUri = $stats->getEffectiveUri();
            }
        ]);

        $body = $response->getBody();

        return [
            'url' => $url,
            'scheme' => $effectiveUri instanceof \GuzzleHttp\Psr7\Uri ? $effectiveUri->getScheme() : NULL,
            'content' => $body->getContents(),
            'headers' => $response->getHeaders(),
            'cookies' => $cookieJar->toArray()
        ];
    }
}