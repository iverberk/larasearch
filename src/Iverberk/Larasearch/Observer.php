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
        $reindexModels = [];

		$paths = Config::get('larasearch::reversedPaths.' . get_class($model), []);

		foreach ((array)$paths as $path)
		{
			if (!empty($path))
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
                        if (!empty($segment))
                        {
                            if (in_array($segment, $record->getRelations()))
                            {
                                $walk($record->getRelation($segment));
                            }
                            else
                            {
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

        Queue::push('Iverberk\Larasearch\Jobs\ReindexJob', array_unique($reindexModels));
	}

}