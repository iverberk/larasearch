<?php namespace Iverberk\Larasearch\Queues;

class ReindexQueue {

    public function fire($job, $models)
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