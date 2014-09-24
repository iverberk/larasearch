<?php namespace Iverberk\Larasearch;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

class Observer {

	public function deleted(Model $model)
	{
	}

	public function saved(Model $model)
	{
		// Temporary array to store affected models
		$reindexModels = [];

		$paths = Config::get('larasearch::reversedPaths.' . get_class($model), []);

		foreach ((array)$paths as $path)
		{
			if ( ! empty($path))
			{
				$model = $model->load($path);

				$path = explode('.', $path);

				// Define a little recursive function to walk the relations of the model based on the path
				// Eventually it will queue all affected searchable models for reindexing
                $walk = function ($relation) use (&$walk, &$path, &$reindexModels)
                {
                    $segment = array_shift($path);

                    $relation = $relation instanceof Collection ? $relation : new Collection([$relation]);

                    foreach ($relation as $record)
                    {
                        if ( ! empty($segment))
                        {
                            if (array_key_exists($segment, $record->getRelations()))
                            {
                                $walk($record->getRelation($segment));
                            }
                            else
                            {
	                            // Apparently the relation doesn't exist on this model, so skip the rest of the path as well
                                return;
                            }
                        }
                        else
                        {
                            $reindexModels[] = get_class($record) . ':' . $record->getKey();
                        }
                    }
                };

				$walk($model->getRelation(array_shift($path)));
			}
			else
			{
                $reindexModels[] = get_class($model) . ':' . $model->getKey();
			}
		}

		// Clean up duplicate entries and push the job on to the queue
        Queue::push('Iverberk\Larasearch\Jobs\ReindexJob', array_unique($reindexModels));
	}

}