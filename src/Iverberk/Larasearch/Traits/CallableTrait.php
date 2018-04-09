<?php

namespace Iverberk\Larasearch\Traits;

use Illuminate\Database\Eloquent\Model;

trait CallableTrait
{
    /**
     * Boot the trait by registering the Larasearch observer with the model
     */
    public static function bootCallableTrait()
    {
        $observerClass = \Config::get('larasearch.observer');
        if (new static instanceof Model) {
            static::observe(new $observerClass);
        } else {
            throw new \Exception("This trait can ony be used in Eloquent models.");
        }
    }
}
