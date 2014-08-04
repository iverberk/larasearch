<?php namespace Iverberk\Larasearch;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;

class Observer {

    public function deleted(Model $model)
    {
        $this->mutate($model);
    }

    public function restored(Model $model)
    {
        $this->mutate($model);
    }

    public function saved(Model $model)
    {
        $this->mutate($model);
    }

    private function mutate(Model $model, $ancestor = '', &$seen = [])
    {
        // Check if we need to index the model
        if ($this->isSearchable($model))
        {
            call_user_func(array($model, 'refreshDoc'), $model);
        }

        // Check if the model has any related classes that need indexing
        $methods = with(new \ReflectionClass($model))->getMethods();

        foreach($methods as $method)
        {
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
                            $data = $relation->getResults();

                            if ($data instanceof Collection)
                            {
                                foreach($data as $record)
                                {
                                    call_user_func(array($related, 'refreshDoc'), $record);
                                }
                            }
                            else
                            {
                                call_user_func(array($related, 'refreshDoc'), $data);
                            }

                        }
                        else
                        {
                            if (  $relation instanceof Model &&
                                ! $relation instanceof $ancestor &&
                                ! in_array($relation, $seen) &&
                                  $this->checkDocHints($docComment))
                            {
                                $seen[] = $relation;

                                // Recursively find a relation that implements the SearchableTrait
                                $this->mutate($relation, $model, $seen);
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

    /**
     * @param $related
     * @return bool
     */
    private function isSearchable($related)
    {
        return in_array('Iverberk\Larasearch\Traits\SearchableTrait', class_uses(get_class($related)));
    }

}