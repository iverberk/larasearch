<?php namespace Iverberk\Larasearch;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue as LaravelQueue;
use Iverberk\Larasearch\Introspect\RelationMapCallback;

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
     * @param \Iverberk\Larasearch\Introspect\RelationMapCallback $callBack
     * @param null $model
     * @param string $ancestor
     * @param array $path
     * @param null $start
     * @return array
     */
    public function relationMap(RelationMapCallback $callBack, $model = null , $ancestor = '', $path = [], $start = null)
    {
        if ($model == null && isset($this->model)) $model = $this->model;

        if ($start == null) $start = $model;

        $relations = [];

        $methods = with(new \ReflectionClass($model))->getMethods();

        foreach($methods as $method)
        {
            $newPath = $path;
            $newPath[] = $method->name;

            $docComment = $method->getDocComment();

            if ($method->class == get_class($model) &&
                stripos($docComment, '@return \Illuminate\Database\Eloquent\Relations'))
            {
                $camelKey = camel_case($method->name);

                try
                {
                    $relation = $model->$camelKey();

                    if ($relation instanceof Relation)
                    {
                        $related = $relation->getRelated();

                        if ( ! $related instanceof $ancestor &&
                             ! $related instanceof $start &&
                            $callBack->proceed($related) &&
                            $this->checkDocHints($docComment))
                        {
                            $relations[] = $related;

                            $this->relationMap($callBack, $related, $model, $newPath, $start);
                        }
                    }
                }
                catch (\BadMethodCallException $e)
                {
                    // Ignore any non-existant methods that may have been called on the model
                }
            }
        }

        $callBack->callback($model, $path, $relations, $start);
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