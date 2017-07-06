<?php namespace Iverberk\Larasearch\Traits;

use Illuminate\Database\Eloquent\Model;
use Iverberk\Larasearch\Observer;

trait CallableTrait {

    /**
     * Boot the trait by registering the Larasearch observer with the model
     */
    public static function bootCallableTrait()
    {
        if (new static instanceof Model)
        {
            static::observe(new Observer);
        } else
        {
            throw new \Exception("This trait can ony be used in Eloquent models.");
        }
    }
}
