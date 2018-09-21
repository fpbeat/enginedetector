<?php

namespace EngineDetector\Http;

use Noodlehaus\Config;
use EngineDetector\Utils\Arr;

trait RandomUserAgent {

    /**
     * @return string
     */
    public static function getRandomUserAgent() {
        $config = Config::load('./config/useragents.php');

        return Arr::getRandom($config->all());
    }
}