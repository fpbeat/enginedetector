<?php

namespace EngineDetector;

use EngineDetector\Utils\Arr;

class DetectResult implements \ArrayAccess {
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
        return $this->get('name');
    }

    /**
     * @return string
     */
    public function getVersion() {
        return $this->get('version');
    }

    /**
     * @return string
     */
    public function getConfidence() {
        return $this->get('confidence');
    }

    /**
     * @return string
     */
    public function getDetectorType() {
        return $this->get('type');
    }

    /**
     * @return string
     */
    public function getHandlerName() {
        return $this->get('handler');
    }

    /**
     * @return mixed|null
     */
    public function getHostName() {
        return $this->get('hostname');
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
    public function all() {
        return $this->data;
    }

    /**
     * @return array
     */
    public function only() {
        return call_user_func_array([Arr::class, 'extract'], [$this->all(), func_get_args()]);
    }

    /**
     * @return string
     * @throws \Exception
     */
    public function toJson() {
        $json = json_encode($this->all());

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(sprintf('Error encoding data to JSON, stopped on: %s', json_last_error()));
        }

        return $json;
    }

    /**
     * @param string $key
     * @param string|null $default
     *
     * @return mixed|null
     */
    private function get($key, $default = NULL) {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * @param mixed $offset
     *
     * @return mixed|null
     */
    public function offsetGet($offset) {
        return $this->get($offset);
    }

    /**
     * @param mixed $offset
     *
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->data[$offset]);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
        $this->data[$offset] = $value;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        unset($this->data[$offset]);
    }

}
