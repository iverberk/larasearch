<?php namespace Iverberk\Larasearch;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Queue;

class Observer {

	/**
	 * Model delete event hanlder
	 *
	 * @param Model $model
	 */
	public function deleted(Model $model)
	{
		Queue::push('Iverberk\Larasearch\Jobs\DeleteJob', [get_class($model) . ':' . $model->getKey()]);

		Queue::push('Iverberk\Larasearch\Jobs\ReindexJob', $this->findAffectedModels($model, true));
	}

	/**
	 * Model save event handler
	 *
	 * @param Model $model
	 */
	public function saved(Model $model)
	{
		Queue::push('Iverberk\Larasearch\Jobs\ReindexJob',$this->findAffectedModels($model));
	}

	/**
	 * Find all searchable models that are affected by the model change
	 *
	 * @param Model $model
	 * @return array
	 */
	private function findAffectedModels(Model $model, $excludeCurrent = false)
	{
		// Temporary array to store affected models
		$affectedModels = [];

		$paths = Config::get('larasearch::reversedPaths.' . get_class($model), []);

		foreach ((array)$paths as $path)
		{
			if ( ! empty($path))
			{
				$model = $model->load($path);

				$path = explode('.', $path);

				// Define a little recursive function to walk the relations of the model based on the path
				// Eventually it will queue all affected searchable models for reindexing
				$walk = function ($relation) use (&$walk, &$path, &$affectedModels)
				{
					$segment = array_shift($path);

					$relation = $relation instanceof Collection ? $relation : new Collection([$relation]);

					foreach ($relation as $record)
					{
						if ($record instanceof Model)
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
								$affectedModels[] = get_class($record) . ':' . $record->getKey();
							}
						}
					}
				};

				$walk($model->getRelation(array_shift($path)));
			}
			else if ( ! $excludeCurrent)
			{
				$affectedModels[] = get_class($model) . ':' . $model->getKey();
			}
		}

		return array_unique($affectedModels);
	}

}
