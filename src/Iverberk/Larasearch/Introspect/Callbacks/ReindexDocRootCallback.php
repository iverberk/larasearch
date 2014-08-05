<?php namespace Iverberk\Larasearch\Introspect\Callbacks;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;
use Iverberk\Larasearch\Introspect\RelationMapCallback;

class ReindexDocRootCallback extends RelationMapCallback {

    private $seen = [];

    public function callback($model, $path, $relations, $start)
    {
        if (Config::has('documents.' . get_class($model)))
        {
            if ( ! empty($path))
            {
                $currentPath = array_shift($path);

                $relation = $start->$currentPath;

                $records = ($relation instanceof Collection) ? $relation : [$relation];

                foreach($records as $record)
                {
                    $this->callback($model, $path, $relations, $record);
                }
            }
            else
            {
                Queue::push('Iverberk\Larasearch\Queues\ReindexQueue', [get_class($start) . ':' . $start->getKey()]);
            }
        }
    }

    public function proceed($related)
    {
        $proceed = in_array(get_class($related), $this->seen) ? false : true;
        $this->seen[] = get_class($related);

        return $proceed;
    }

} 