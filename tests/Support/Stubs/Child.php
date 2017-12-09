<?php

use Iverberk\Larasearch\Traits\SearchableTrait;

class Child extends Illuminate\Database\Eloquent\Model {

    use SearchableTrait;

    /**
     * @follow UNLESS Husband
     *
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function mother()
    {
        return $this->belongsTo('Wife');
    }

    /**
     * @follow UNLESS Husband
     * @follow UNLESS Wife
     *
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function father()
    {
        return $this->belongsTo('Husband');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function toys()
    {
        return $this->belongsToMany('Toy');
    }

}

