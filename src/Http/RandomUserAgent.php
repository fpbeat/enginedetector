<?php

namespace EngineDetector\Http;

use Noodlehaus\Config;
use EngineDetector\Utils\Arr;

trait RandomUserAgent {

    /**
     * @return string
     */
    public static function getRandomUserAgent() {
        $config = Config::load(ENGINE_DETECTOR_DOCROOT . 'config/useragents.php');

        return Arr::getRandom($config->all());
    }
}