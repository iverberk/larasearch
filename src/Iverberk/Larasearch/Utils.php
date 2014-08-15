<?php namespace Iverberk\Larasearch;

use DirectoryIterator;

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
     * Removed the pass-by-reference to accomdate unit-testing
     *
     * @param array $array1
     * @param array $array2
     * @return array
     */
    public static function array_merge_recursive_distinct ( array $array1, array $array2 )
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

    public static function findSearchableModels($directories)
    {
        $models = [];

        // Iterate over each directory and inspect files for models
        foreach($directories as $directory)
        {
            $dir = new DirectoryIterator($directory);
            foreach ($dir as $fileinfo)
            {
                $namespace = '';

                if (!$fileinfo->isDot() && $fileinfo->isReadable())
                {
                    $fileObj = $fileinfo->openFile('r');

                    while (!$fileObj->eof())
                    {
                        $line = $fileObj->fgets();

                        // Extract namespace
                        if (preg_match('/namespace\s+([a-zA-z0-9]+)/', $line, $matches))
                        {
                            $namespace = $matches[1];
                        }

                        // Extract classname
                        if (preg_match('/class\s+([a-zA-z0-9]+)/', $line, $matches))
                        {
                            $model = $namespace ? $namespace . '\\' . $matches[1] : $matches[1];

                            // Check if the model has the searchable trait
                            if (in_array('Iverberk\\Larasearch\\Traits\\SearchableTrait', class_uses($model)))
                            {
                                $models[] = $model;
                            }
                        }
                    }
                }
            }
        }

        return $models;
    }

}