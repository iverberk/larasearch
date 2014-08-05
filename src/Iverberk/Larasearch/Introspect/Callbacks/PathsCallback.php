<?php namespace Iverberk\Larasearch\Introspect\Callbacks;

use Iverberk\Larasearch\Introspect\RelationMapCallback;

class PathsCallback extends RelationMapCallback {

    private $paths = [];

    public function callback($model, $path, $relations, $start)
    {
        if (empty($relations))
        {
            $this->paths[] = implode('.', $path);
        }
    }

    public function getPaths()
    {
        return $this->paths;
    }

} 