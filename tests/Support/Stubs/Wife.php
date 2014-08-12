<?php

use Iverberk\Larasearch\Traits\TransformableTrait;

class Wife extends Illuminate\Database\Eloquent\Model {

    use TransformableTrait;

    /**
     * @follow NEVER
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