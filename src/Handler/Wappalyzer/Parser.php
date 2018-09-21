<?php

namespace EngineDetector\Handler\Wappalyzer;

class Parser {

    /**
     * @var array|null
     */
    private $successMatch = NULL;

    /**
     * Parser constructor.
     *
     * @param string $url
     * @param string $content
     * @param array $headers
     * @param array $cookies
     */
    public function __construct($url, $content, array $headers, array $cookies) {
        $this->headers = $headers;
        $this->variables = Variables::factory($content, $url, $headers, $cookies);
    }

    /**
     * @param mixed $patterns
     *
     * @return bool
     */
    public function isHtmlMatch($patterns) {
        foreach ($this->parse($patterns) as $pattern) {
            if (preg_match(sprintf('#%s#i', $pattern['regex']), $this->variables->getContent(), $match)) {
                $this->setSuccessMatch($pattern, $match);

                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * @param mixed $patterns
     *
     * @return bool
     */
    public function isScriptMatch($patterns) {
        foreach ($this->variables->getScripts() as $script) {
            foreach ($this->parse($patterns) as $pattern) {
                if (preg_match(sprintf('#%s#i', $pattern['regex']), $script, $match)) {
                    $this->setSuccessMatch($pattern, $match);

                    return TRUE;
                }
            }
        }

        return FALSE;
    }

    /**
     * @param mixed $patterns
     *
     * @return bool
     */
    public function isMetaMatch($patterns) {
        foreach ($this->variables->getMeta() as $meta) {
            foreach ($patterns as $name => $rules) {
                foreach ($this->parse($rules) as $pattern) {
                    if ($meta['name'] === strtolower($name) && preg_match(sprintf('#%s#i', $pattern['regex']), $meta['content'], $match)) {
                        $this->setSuccessMatch($pattern, $match);

                        return TRUE;
                    }
                }
            }
        }

        return FALSE;
    }

    /**
     * @param mixed $patterns
     *
     * @return bool
     */
    public function isHeadersMatch($patterns) {
        foreach ($this->variables->getHeaders() as $header) {
            foreach ($patterns as $name => $rules) {
                foreach ($this->parse($rules) as $pattern) {
                    if ($header['name'] === strtolower($name) && preg_match(sprintf('#%s#i', $pattern['regex']), $header['value'], $match)) {
                        $this->setSuccessMatch($pattern, $match);

                        return TRUE;
                    }
                }
            }
        }

        return FALSE;
    }

    /**
     * @param mixed $patterns
     *
     * @return bool
     */
    public function isUrlMatch($patterns) {
        foreach ($this->parse($patterns) as $pattern) {
            if (preg_match(sprintf('#%s#i', $pattern['regex']), $this->variables->getUrl(), $match)) {
                $this->setSuccessMatch($pattern, $match);

                return TRUE;
            }
        }

        return FALSE;
    }

    /**
     * @param mixed $patterns
     *
     * @return bool
     */
    public function isCookiesMatch($patterns) {
        foreach ($this->variables->getCookies() as $cookieName => $cookieValue) {
            foreach ($patterns as $name => $rules) {
                foreach ($this->parse($rules) as $pattern) {
                    if ($cookieName === strtolower($name) && preg_match(sprintf('#%s#i', $pattern['regex']), $cookieValue, $match)) {
                        $this->setSuccessMatch($pattern, $match);

                        return TRUE;
                    }
                }
            }
        }

        return FALSE;
    }

    /**
     * @param mixed $patterns
     *
     * @return array
     */
    private function parse($patterns) {
        $attrs = NULL;
        $parsed = [];

        $patterns = is_string($patterns) ? [$patterns] : $patterns;

        foreach ($patterns as $pattern) {
            $attrs = [];
            $parts = explode('\\;', $pattern);

            foreach ($parts as $i => $attr) {
                if ($i) {
                    $attr = explode(':', $attr);
                    if (count($attr) > 1) {
                        $attrs[array_shift($attr)] = implode(':', $attr);
                    }
                } else {
                    $attrs['string'] = $attr;
                    $attrs['regex'] = str_replace('/', '\/', $attr);
                }
            }

            array_push($parsed, $attrs);
        }

        return $parsed;
    }

    /**
     * @param string $pattern
     * @param array $match
     */
    private function setSuccessMatch($pattern, $match) {
        $this->successMatch = [$pattern, $match];
    }

    /**
     * @return array|null
     */
    public function getSuccessMatch() {
        return $this->successMatch;
    }
}