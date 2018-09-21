<?php

namespace EngineDetector;

class DetectResult {
    /**
     * @var array
     */
    private $data = [];

    /**
     * @param array $data
     */
    public function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->data['engine'];
    }

    /**
     * @return string
     */
    public function getVersion() {
        return $this->data['version'];
    }

    /**
     * @return string
     */
    public function getConfidence() {
        return $this->data['confidence'];
    }

    /**
     * @return string
     */
    public function getDetectorType() {
        return $this->data['type'];
    }

    /**
     * @return string
     */
    public function getHandlerName() {
        return $this->data['handler'];
    }

    /**
     * @param array $data
     *
     * @return DetectResult
     */
    public static function fromArray(array $data) {
        return new self($data);
    }

    /**
     * @return array
     */
    public function toArray() {
        return $this->data;
    }
}
