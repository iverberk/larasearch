<?php namespace Iverberk\Larasearch\Jobs;

use Illuminate\Queue\Jobs\Job;

class DeleteJob {

	public function fire(Job $job, $models)
	{
		foreach ($models as $model)
		{
			list($class, $id) = explode(':', $model);

			$model = new $class;

			$model->deleteDoc($id);
		}

		$job->delete();
	}

} 