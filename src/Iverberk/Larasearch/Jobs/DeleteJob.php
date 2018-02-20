<?php namespace Iverberk\Larasearch\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Config\Repository;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Class DeleteJob
 *
 * @package Iverberk\Larasearch\Jobs
 */
class DeleteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Config
     */
    protected $config;

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
        $logger = \App::make($config->get('larasearch.logger'));

        foreach ($this->models as $model) {
            list($class, $id) = explode(':', $model);

            $logger->info('Deleting ' . $class . ' with ID: ' . $id . ' from Elasticsearch');

            $model = new $class;

            $model->deleteDoc($id);
        }

        $this->delete();
    }
}
