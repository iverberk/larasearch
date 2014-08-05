<?php namespace Iverberk\Larasearch;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Config;
use Iverberk\Larasearch\Introspect\Callbacks\ReindexDocRootCallback;

class Observer {

    public function deleted(Model $model)
    {
    }

    public function saved(Model $model)
    {
        with(new Introspect($model))->relationMap(new ReindexDocRootCallback);
    }

}