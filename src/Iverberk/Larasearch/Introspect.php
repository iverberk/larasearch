<?php namespace Iverberk\Larasearch;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class Introspect {

    private $model;

    /**
     * Construct the introspection from an Eloquent model
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Get an array of column descriptors for a model
     *
     * @return \Doctrine\DBAL\Schema\Column[]
     */
    public function getColumns()
    {
        $table = $this->model->getTable();

        $schema = $this->model->getConnection()->getDoctrineSchemaManager($table);

        return $schema->listTableColumns($table);
    }

    /**
     * Find all related Eloquent models that are in some way connected to the base model
     * and return them as an array
     *
     * @param null $base
     * @param string $ancestor
     * @param array $seen
     * @return array
     */
    public function getRelatedModels($base = null, $ancestor = '', &$seen = [])
    {
        $model = $base ?: $this->model;
        $reflectionClass = new \ReflectionClass($model);

        $relations = [];
        $methods = $reflectionClass->getMethods();

        foreach($methods as $method)
        {
            $docComment = $method->getDocComment();

            // Check that the method is not inherited and read the docblock
            // for a hint that it returns an Eloquent relation
            if ($method->class == $reflectionClass->getName() &&
                stripos($docComment, '@return \Illuminate\Database\Eloquent\Relations'))
            {
                $camelKey = camel_case($method->name);

                try {
                    $relation = $model->$camelKey();

                    if ($relation instanceof Relation)
                    {
                        $related = $relation->getRelated();
                        $relatedClassName = get_class($related);

                        if ( ! $related instanceof $ancestor &&
                             ! $related instanceof $this->model &&
                             ! in_array($relatedClassName, $seen) &&
                            $this->checkDocHints($docComment))
                        {
                            $seen[] = $relatedClassName;

                            // Recursively find relations of the related model
                            $relations[$relatedClassName] =
                                [
                                    'method' => $method->name,
                                    'instance' => $related,
                                    'related' => $this->getRelatedModels($related, $model, $seen)
                                ];
                        }
                    }
                }
                catch (\BadMethodCallException $e)
                {
                    // Ignore any non-existant methods that may have been called on the model
                }
            }
        }

        return $relations;
    }

    /**
     * Get related Eloquent as dot separated paths
     *
     * @access public
     * @param array $models
     * @return array
     */
    public function getPaths($models = null)
    {
        $models = $models ?: $this->getRelatedModels();
        $paths = [];

        foreach($models as $model)
        {
            if(empty($model['related']))
            {
                $paths[] = $model['method'];
            }
            else
            {
                $childPaths = $this->getPaths($model['related']);

                foreach($childPaths as $childPath)
                {
                    $paths[] = $model['method'] . "." . $childPath;
                }
            }
        }

        return $paths;
    }

    /**
     * @param string $docComment
     * @return bool
     */
    private function checkDocHints($docComment)
    {
        // Check if we never follow this relation
        if (preg_match('/@follow NEVER/', $docComment)) return false;

        // Check if we follow the relation from the 'base' model
        if (preg_match('/@follow UNLESS ([\\\\0-9a-zA-Z]+)/', $docComment, $matches))
        {
            if ($matches[1] === get_class($this->model)) return false;
        }

        // We follow the relation
        return true;
    }

}