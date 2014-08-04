<?php namespace Iverberk\Larasearch;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;

class Observer {

    public function deleted(Model $model)
    {
        // $this->mutate($model);
    }

    public function restored(Model $model)
    {
        // $this->mutate($model);
    }

    public function saved(Model $model)
    {
        $models = $this->mutate($model);

        foreach($models as $model)
        {
            $model->refreshDoc($model);
        }
    }

    private function mutate(Model $model, $ancestor = '', &$seen = [], $path = [], $start = null)
    {
        if ($start == null) $start = $model;

        $seen[] = $model;
        $foundModels = [];

        // Check if we need to index the model
        if ($this->isSearchable($model))
        {
            call_user_func(array($model, 'refreshDoc'), $model);
        }

        // Check if the model has any related classes that need indexing
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
                    // Grab the results from the relation
                    $relation = $model->$camelKey();

                    if ($relation instanceof Relation)
                    {
                        $related = $relation->getRelated();

                        if ($this->isSearchable($related))
                        {
                            $resolveModels = function($paths, $start) use (&$resolveModels)
                            {
                                $resolvedModels = [];

                                $path = array_shift($paths);

                                $data = $start->$path;

                                if ( ! empty($paths))
                                {
                                    $records = ($data instanceof Collection) ? $data : new Collection(array($data));

                                    foreach($records as $record)
                                    {
                                        $resolvedModels = array_merge($resolvedModels, $resolveModels($paths, $record));
                                    }
                                }
                                else
                                {
                                    $resolvedModels = ($data instanceof Collection) ? $data->toArray() : [$data];
                                }

                                return $resolvedModels;
                            };

                            $foundModels = $resolveModels($newPath, $start);
                        }
                        else
                        {
                            if (  $related instanceof Model &&
                                ! $related instanceof $ancestor &&
                                ! in_array($related, $seen) &&
                                  $this->checkDocHints($docComment, $start))
                            {

                                // Recursively find a relation that implements the SearchableTrait
                                $foundModels = array_merge($foundModels, $this->mutate($related, $model, $seen, $newPath, $start));
                            }
                        }
                    }
                }
                catch (LogicException $e)
                {
                    // The getAttribute method doesn't return an Eloquent relation so we ignore it
                }
            }
        }

        return $foundModels;
    }

    /**
     * @param string $docComment
     * @return bool
     */
    private function checkDocHints($docComment, $start)
    {
        // Check if we never follow this relation
        if (preg_match('/@follow NEVER/', $docComment)) return false;

        // Check if we follow the relation from the 'base' model
        if (preg_match('/@follow UNLESS ([\\\\0-9a-zA-Z]+)/', $docComment, $matches))
        {
            if ($matches[1] === get_class($start)) return false;
        }

        // We follow the relation
        return true;
    }

    /**
     * @param $related
     * @return bool
     */
    private function isSearchable($related)
    {
        return in_array('Iverberk\Larasearch\Traits\SearchableTrait', class_uses(get_class($related)));
    }

}