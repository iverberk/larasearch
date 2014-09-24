<?php namespace Iverberk\Larasearch\Jobs;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Queue\Jobs\Job;

class DeleteJob {

	public function fire(Job $job, $models)
	{
		try
		{
			foreach ($models as $model)
			{
				list($class, $id) = explode(':', $model);

				$model = new $class;

				$model->deleteDoc($id);
			}

			$job->delete();
		} catch (ModelNotFoundException $e)
		{
			$job->release(60);
		}
	}

} 