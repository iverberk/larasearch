<?php namespace Iverberk\Larasearch\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Class ReindexJob
 *
 * @package Iverberk\Larasearch\Jobs
 */
class ReindexJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var $models
     */
    protected $models;

    /**
     * @param $models
     */
    public function __construct($models)
    {
        $this->models = $models;
    }

    /**
     * @param Repository $config
     */
    public function handle(Repository $config)
    {
        $logger = App::make($config->get('larasearch.logger'));

        foreach ($this->models as $model) {
            list($class, $id) = explode(':', $model);

            $logger->info('Indexing ' . $class . ' with ID: ' . $id);

            try {
                $model = $class::findOrFail($id);
                $model->refreshDoc($model);
            } catch (Exception $e) {
                $logger->error('Indexing ' . $class . ' with ID: ' . $id . ' failed: ' . $e->getMessage());

                $this->release(60);
            }
        }

        $this->delete();
    }
}
