<?php

use Iverberk\Larasearch\Traits\SearchableTrait;

class Wife extends Illuminate\Database\Eloquent\Model {

    use SearchableTrait;

    /**
     * @follow UNLESS Toy
     * @follow UNLESS Child
     *
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function husband()
    {
        return $this->belongsTo('Husband');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function children()
    {
        return $this->hasMany('Child', 'mother_id');
    }

}