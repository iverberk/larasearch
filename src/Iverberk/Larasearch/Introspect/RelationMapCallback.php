<?php namespace Iverberk\Larasearch\Introspect;

abstract class RelationMapCallback {

    public abstract function callback($model, $path, $relations, $start);

    public function proceed($related) {
        return true;
    }
}