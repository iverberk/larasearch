<?php

use Iverberk\Larasearch\Traits\TransformableTrait;

class Child extends Illuminate\Database\Eloquent\Model {

    use TransformableTrait;

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function mother()
    {
        return $this->belongsTo('Wife');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function father()
    {
        return $this->belongsTo('Husband');
    }

    /**
     * @follow UNLESS Wife
     *
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function toys()
    {
        return $this->belongsToMany('Toy');
    }

}

