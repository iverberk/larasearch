<?php namespace Iverberk\Larasearch;

class Utils {

    /**
     * Read and return parameters from an array.
     *
     * @param array $params
     * @param string $arg
     *
     * @param null $default
     * @return null|mixed
     */
    public static function findKey($params, $arg, $default = null)
    {
        if (is_object($params) === true) {
            $params = (array)$params;
        }

        if (isset($params[$arg]) === true) {
            $val = $params[$arg];
            unset($params[$arg]);
            return $val;
        } else {
            return $default;
        }
    }

    /**
     * Taken from http://php.net/manual/en/function.array-merge-recursive.php#92195
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function array_merge_recursive_distinct ( array &$array1, array &$array2 )
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value)
        {
            if (is_array ($value) && isset ($merged[$key]) && is_array ($merged[$key]))
            {
                $merged[$key] = self::array_merge_recursive_distinct($merged[$key], $value);
            }
            else
            {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

}