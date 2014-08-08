<?php namespace Iverberk\Larasearch\Jobs;

use Illuminate\Queue\Jobs\Job;

class ReindexJob {

    public function fire(Job $job, $models)
    {
        try
        {
            foreach($models as $model)
            {
                list($class, $id) = explode(':', $model);

                $model = $class::findOrFail($id);

                $model->refreshDoc($model);
            }

            $job->delete();
        }
        catch (\Exception $e)
        {
            $job->release(60);
        }
    }

} 