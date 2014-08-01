<?php namespace Iverberk\Larasearch;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class Observer {

    public function updated(Model $model)
    {
        $this->mutate($model);
    }

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

    private function mutate(Model $model, $ancestor = '')
    {
        $methods = with(new \ReflectionClass($model))->getMethods();

        foreach($methods as $method)
        {
            if ($method->class == get_class($model))
            {
                try
                {
                    // Grab the results from the relation
                    $attribute = $model->getAttribute($method->name);
                    $relation = ($attribute instanceof Collection) ? $attribute->first() : $attribute;

                    if (in_array('Iverberk\Larasearch\Traits\SearchableTrait', class_uses(get_class($relation))))
                    {
                        call_user_func(array($relation, 'reindex'), $relation->id);
                    }
                    else
                    {
                        if ($relation instanceof Model && ! $relation instanceof $ancestor)
                        {
                            // Recursively find a relation that implements the SearchableTrait
                            $this->mutate($relation, $model);
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

}