<?php

namespace EngineDetector;

use EngineDetector\Exception\InvalidUrlException;
use EngineDetector\Exception\RequestException;
use EngineDetector\Exception\UnknownEngineException;
use EngineDetector\Handler\AbstractHandler;
use EngineDetector\Utils\Arr;

class Detector {

    /**
     * @var array $handlers
     */
    private $handlers = [];

    /**
     * @var mixed
     */
    private $cache;

    /**
     * Detector constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = []) {
        $this->config = $config;

        if (isset($this->config['caching'])) {
            $this->cache = Cache::instance($this->config['caching']);
        }
    }

    /**
     * @param string $url
     * @param string|null $component
     *
     * @return array|string
     * @throws InvalidUrlException
     */
    public function processURL($url, $component = NULL) {
        $url = trim($url);

        if (!preg_match('/^https?:\/\//iu', $url)) {
            $url = sprintf('http://%s', $url);
        }

        if (!filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED)) {
            throw new InvalidUrlException('Invalid input URL');
        }

        $hostname = preg_replace('/^www./iu', '', parse_url($url, PHP_URL_HOST));
        $output = ['hostname' => $hostname, 'url' => $url];

        return Arr::get($output, $component, array_values($output));
    }

    /**
     * @param AbstractHandler $handler
     */
    public function addHandler(AbstractHandler $handler) {
        array_push($this->handlers, $handler);
    }

    /**
     * @param string $url
     *
     * @return DetectResult
     * @throws InvalidUrlException
     * @throws RequestException
     * @throws UnknownEngineException;
     */
    public function detect($url) {
        try {
            list($hostname, $url) = $this->processURL($url);

            if ($this->cache && $this->cache->has($hostname)) {
                return DetectResult::fromArray($this->cache->get($hostname, []));
            }

            foreach ($this->handlers as $handler) {
                if (($result = $handler->detect($url, $hostname)) instanceof DetectResult) {
                    if ($this->cache) {
                        $this->cache->set($hostname, $result->all());
                    }

                    return $result;
                }
            }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            throw new RequestException($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            throw $e;
        }

        throw new UnknownEngineException;
    }
}