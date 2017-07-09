<?php namespace Iverberk\Larasearch;

use RegexIterator;
use PHPParser_Lexer;
use PHPParser_Parser;
use RecursiveIteratorIterator;
Use PHPParser_Node_Stmt_Class;
use RecursiveDirectoryIterator;
use PHPParser_Node_Stmt_Namespace;

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
        if (is_object($params) === true)
        {
            $params = (array)$params;
        }

        if (isset($params[$arg]) === true)
        {
            $val = $params[$arg];
            unset($params[$arg]);

            return $val;
        } else
        {
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
    public static function array_merge_recursive_distinct(array $array1, array $array2)
    {
        $merged = $array1;

        foreach ($array2 as $key => &$value)
        {
            if (is_array($value) && isset ($merged[$key]) && is_array($merged[$key]))
            {
                $merged[$key] = self::array_merge_recursive_distinct($merged[$key], $value);
            } else
            {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    public static function findSearchableModels($directories)
    {
        $models = [];
        $parser = new PHPParser_Parser(new PHPParser_Lexer);

        // Iterate over each directory and inspect files for models
        foreach ($directories as $directory)
        {
            // iterate over all .php files in the directory
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
            $files = new RegexIterator($files, '/\.php$/');

            foreach ($files as $file)
            {
                // read the file that should be converted
                $code = file_get_contents($file);

                // parse
                $stmts = $parser->parse($code);

                $walk = function ($stmt, $key, $ns) use (&$models, &$walk)
                {
                    if ($stmt instanceof PHPParser_Node_Stmt_Namespace)
                    {
                        $new_ns = implode('\\', $stmt->name->parts);
                        if ($ns && strpos($new_ns, $ns) !== 0) $new_ns = $ns . $new_ns;
                        array_walk($stmt->stmts, $walk, $new_ns);
                    } else if ($stmt instanceof PHPParser_Node_Stmt_Class)
                    {
                        $class = $stmt->name;
                        if ($ns) $class = $ns . '\\' . $class;
                        if (in_array('Iverberk\\Larasearch\\Traits\\SearchableTrait', class_uses($class)))
                        {
                            $models[] = $class;
                        }
                    }
                };

                array_walk($stmts, $walk, '');
            }
        }

        return $models;
    }
}
