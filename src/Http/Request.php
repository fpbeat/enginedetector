<?php

namespace EngineDetector\Http;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request as GuzzleRequest;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;


class Request {

    use RandomUserAgent;

    protected $notRetryCodes = [200, 301, 302];
    protected $retryDelay = 1000;

    protected $attempts = 2;
    protected $handler;
    protected $logger;

    /**
     * Request constructor.
     *
     * @param $hander
     */
    public function __construct($hander) {
        $this->handler = $hander;
    }

    /**
     * @param null $handler
     *
     * @return Request
     */
    public static function factory($handler = NULL) {
        $stack = HandlerStack::create($handler);

        return new self($stack);
    }

    /**
     * @param array $options
     *
     * @return Client
     */
    public function create($options = []) {
        $this->setOptions($options);

        $this->handler->push(Middleware::retry($this->retryMiddleware(), function () {
            return $this->retryDelay;
        }));

        return new Client(array_merge([
            'handler' => $this->handler,

            'cookies' => TRUE,
            'delay' => 500,
            'timeout' => 5,

            'headers' => [
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, sdch',
                'Accept-Language' => 'uk-UA,uk;q=0.8,ru;q=0.6,en-US;q=0.4,en;q=0.2',
                'Cache-Control' => 'max-age=0',
            ],
            'debug' => FALSE,
        ], $options));
    }

    /**
     * @return \Closure
     */
    protected function retryMiddleware() {
        return function ($retries, GuzzleRequest $request, Response $response = NULL, RequestException $exception = NULL) {
            if ($retries >= $this->attempts) {
                return FALSE;
            }

            if (($response && in_array($response->getStatusCode(), $this->notRetryCodes) === FALSE) || $exception instanceof \GuzzleHttp\Exception\RequestException) {
                return TRUE;
            }

            return FALSE;
        };
    }

    /**
     * @param array $options
     */
    private function setOptions(array $options) {
        if (isset($options['notRetryCodes'])) {
            $this->notRetryCodes = $options['notRetryCodes'];
        }

        if (isset($options['attempts'])) {
            $this->attempts = intval($options['attempts']);
        }
    }
}