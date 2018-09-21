<?php

namespace EngineDetector\Handler;

use EngineDetector\Exception\InvalidUrlException;
use Noodlehaus\Config;

abstract class AbstractHandler {

    /**
     * @var \Noodlehaus\Config $config
     */
    protected $config;

    /**
     * AbstractHandler constructor.
     */
    public function __construct() {
        $this->config = Config::load('./config/detector.php');
    }

    /**
     * @param array $params
     *
     * @return mixed
     */
    abstract public function setDetected(array $params);

    /**
     * @param string $url
     *
     * @return mixed
     */
    abstract public function detect($url);
}