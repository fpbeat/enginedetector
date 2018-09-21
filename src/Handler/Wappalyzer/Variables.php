<?php

namespace EngineDetector\Handler\Wappalyzer;

use DiDom\Document;

class Variables {

    /**
     * @var string|null
     */
    private $content = NULL;
    /**
     * @var string|null
     */
    private $url = NULL;

    /**
     * @var array $headers
     */
    private $headers = [];
    /**
     * @var array $cookies
     */
    private $cookies = [];
    /**
     * @var array $scripts
     */
    private $scripts = [];
    /**
     * @var array $meta
     */
    private $meta = [];

    /**
     * @param string $content
     * @param string $url
     * @param array $headers
     * @param array $cookies
     *
     * @return Variables
     */
    public static function factory($content, $url, array $headers, array $cookies) {
        return new self($content, $url, $headers, $cookies);
    }

    /**
     * Variables constructor.
     *
     * @param string $content
     * @param string $url
     * @param array $headers
     * @param array $cookies
     */
    public function __construct($content, $url, array $headers, array $cookies) {
        $document = new Document($content);

        $this->setContent($document);
        $this->setMeta($document);
        $this->setScripts($document);
        $this->setUrl($url);
        $this->setHeaders($headers);
        $this->setCookies($cookies);
    }

    /**
     * @param Document $document
     */
    private function setScripts(\DiDom\Document $document) {
        foreach ($document->find('script') as $script) {
            array_push($this->scripts, $script->attr('src'));
        }
    }

    /**
     * @param Document $document
     */
    private function setContent(\DiDom\Document $document) {
        $this->content = $document->html();
    }

    /**
     * @param Document $document
     */
    private function setMeta(\DiDom\Document $document) {
        foreach ($document->find('meta') as $meta) {
            $name = strtolower($meta->attr('name'));

            if (!empty($name)) {
                array_push($this->meta, [
                    'name' => $name,
                    'content' => $meta->attr('content')
                ]);
            }
        }
    }

    /**
     * @param array $headers
     */
    private function setHeaders(array $headers) {
        foreach ($headers as $name => $header) {
            $header = is_array($header) ? $header : [$header];

            if (!empty($name)) {
                foreach ($header as $value) {
                    array_push($this->headers, [
                        'name' => strtolower($name),
                        'value' => $value
                    ]);
                }
            }
        }
    }

    /**
     * @param array $cookies
     */
    protected function setCookies(array $cookies) {
        foreach ($cookies as $cookie) {
            $input = array_change_key_case($cookie, CASE_LOWER);
            $name = strtolower($input['name']);

            if (!empty($name)) {
                $this->cookies[$name] = $input['value'];
            }
        }
    }

    /**
     * @param string $url
     */
    private function setUrl($url) {
        $this->url = $url;
    }

    /**
     * @return string|null
     */
    public function getUrl() {
        return $this->url;
    }

    /***
     * @return array
     */
    public function getMeta() {
        return $this->meta;
    }

    /**
     * @return array
     */
    public function getScripts() {
        return $this->scripts;
    }

    /**
     * @return array
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * @return array
     */
    public function getCookies() {
        return $this->cookies;
    }
}