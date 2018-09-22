<?php

namespace EngineDetector\Utils;

class Arr {
    const DELIMITER = '.';

    /**
     * @param callable $func
     * @param array $array
     *
     * @return mixed
     */
    public static function arrayMapRecursive(callable $func, array $array) {
        return filter_var($array, \FILTER_CALLBACK, ['options' => $func]);
    }

    /**
     * @param array $input
     * @param array $order
     *
     * @return array
     */
    public static function orderByKeys(array $input, array $order) {
        $ordered = [];
        foreach ($order as $key) {
            if (array_key_exists($key, $input)) {
                $ordered[$key] = $input[$key];

                unset($input[$key]);
            }
        }

        return $ordered + $input;
    }

    /**
     * @param array $input
     * @param null $default
     *
     * @return null
     */
    public static function getRandom(array $input, $default = NULL) {
        return $input[mt_rand(0, count($input) - 1)] ?: $default;
    }

    /**
     * @param array $input
     *
     * @return string
     */
    public static function getHash(array $input) {
        array_multisort($input);

        return md5(json_encode($input));
    }

    /**
     * @param array $array
     * @param string|array $path
     * @param array $value
     * @param string|null $delimiter
     */
    public static function setPath(&$array, $path, $value, $delimiter = NULL) {
        if (!$delimiter) {
            $delimiter = self::DELIMITER;
        }

        $keys = $path;
        if (!is_array($path)) {
            $keys = explode($delimiter, $path);
        }

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (ctype_digit($key)) {
                $key = (int)$key;
            }

            if (!isset($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
    }

    /**
     * @param array $array
     * @param array $paths
     * @param string|null $default
     *
     * @return array
     */
    public static function extract($array, array $paths, $default = NULL) {
        $found = [];
        foreach ($paths as $path) {
            Arr::setPath($found, $path, Arr::path($array, $path, $default));
        }

        return $found;
    }

    /**
     * @param array $array
     * @param string $path
     * @param string|null $default
     * @param string|null $delimiter
     *
     * @return mixed|null
     */
    public static function path($array, $path, $default = NULL, $delimiter = NULL) {
        if (!is_array($array)) {
            return $default;
        }

        if (is_array($path)) {
            $keys = $path;
        } else {
            if (array_key_exists($path, $array)) {
                return $array[$path];
            }

            if ($delimiter === NULL) {
                $delimiter = self::DELIMITER;
            }

            $path = ltrim($path, "{$delimiter} ");
            $path = rtrim($path, "{$delimiter} *");

            $keys = explode($delimiter, $path);
        }

        do {
            $key = array_shift($keys);

            if (ctype_digit($key)) {
                $key = (int)$key;
            }

            if (isset($array[$key])) {
                if ($keys) {
                    if (is_array($array[$key])) {
                        $array = $array[$key];
                    } else {
                        break;
                    }
                } else {
                    return $array[$key];
                }
            } elseif ($key === '*') {
                $values = [];
                foreach ($array as $arr) {
                    if ($value = Arr::path($arr, implode('.', $keys))) {
                        $values[] = $value;
                    }
                }

                if ($values) {
                    return $values;
                } else {
                    break;
                }
            } else {
                break;
            }
        } while ($keys);

        return $default;
    }

    /**
     * @param array $array
     * @param string $key
     * @param string|null $default
     *
     * @return string|null
     */
    public static function get($array, $key, $default = NULL) {
        if ($array instanceof \ArrayObject) {
            return $array->offsetExists($key) ? $array->offsetGet($key) : $default;
        } else {
            return isset($array[$key]) ? $array[$key] : $default;
        }
    }
}