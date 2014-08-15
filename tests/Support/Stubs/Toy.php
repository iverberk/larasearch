<?php

use Iverberk\Larasearch\Traits\SearchableTrait;

class Toy extends Illuminate\Database\Eloquent\Model {

    use SearchableTrait;

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function children()
    {
        return $this->belongsToMany('Child');
    }

}