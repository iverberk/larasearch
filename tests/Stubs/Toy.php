<?php

use Iverberk\Larasearch\Traits\TransformableTrait;

class Toy extends Illuminate\Database\Eloquent\Model {

    use TransformableTrait;

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function children()
    {
        return $this->belongsToMany('Child');
    }

}