<?php namespace Iverberk\Larasearch;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;

class Observer
{

    /**
     * Model delete event handler
     *
     * @param Model $model
     */
    public function deleted(Model $model)
    {
        // Delete corresponding $model document from Elasticsearch
        Jobs\DeleteJob::dispatch([get_class($model) . ':' . $model->getKey()])
            ->OnQueue(Config::get('larasearch.queue'));

        // Update all related model documents to reflect that $model has been removed
        Jobs\ReindexJob::dispatch($this->findAffectedModels($model, true))
            ->OnQueue(Config::get('larasearch.queue'));
    }

    /**
     * Model save event handler
     *
     * @param Model $model
     */
    public function saved(Model $model)
    {
        if ($model::$__es_enable && $model->shouldIndex()) {
            Jobs\ReindexJob::dispatch($this->findAffectedModels($model))
                ->OnQueue(Config::get('larasearch.queue'));
        }
    }

    /**
     * Find all searchable models that are affected by the model change
     *
     * @param Model $model
     *
     * @return array
     */
    public function findAffectedModels(Model $model, $excludeCurrent = false)
    {
        // Temporary array to store affected models
        $affectedModels = [];

        $paths = Config::get('larasearch.reversedPaths.' . get_class($model), []);

        foreach ((array)$paths as $path) {
            if ( ! empty($path)) {
                if ( ! array_key_exists($path, $model->getRelations())) {
                    $model = $model->load($path);
                }
                // Explode the path into an array
                $path = explode('.', $path);

                // Define a little recursive function to walk the relations of the model based on the path
                // Eventually it will queue all affected searchable models for reindexing
                $walk = function ($relation, array $path) use (&$walk, &$affectedModels) {
                    $segment = array_shift($path);

                    $relation = $relation instanceof Collection ? $relation : new Collection([$relation]);

                    foreach ($relation as $record) {
                        if ($record instanceof Model) {
                            if ( ! empty($segment)) {
                                if (array_key_exists($segment, $record->getRelations())) {
                                    $walk($record->getRelation($segment), $path);
                                } else {
                                    // Apparently the relation doesn't exist on this model, so skip the rest of the path as well
                                    return;
                                }
                            } elseif (in_array('Iverberk\Larasearch\Traits\SearchableTrait', class_uses($record))) {
                                $affectedModels[] = get_class($record) . ':' . $record->getKey();
                            }
                        }
                    }
                };

                $walk($model->getRelation(array_shift($path)), $path);
            } elseif ( ! $excludeCurrent && in_array('Iverberk\Larasearch\Traits\SearchableTrait', class_uses($model))) {
                $affectedModels[] = get_class($model) . ':' . $model->getKey();
            }
        }

        return array_unique($affectedModels);
    }
}
