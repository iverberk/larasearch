<?php namespace Iverberk\Larasearch\Jobs;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Queue\Jobs\Job;

/**
 * Class DeleteJob
 *
 * @package Iverberk\Larasearch\Jobs
 */
class DeleteJob {

    /**
     * @var Application
     */
    private $app;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Application $app
     * @param Config
     */
    public function __construct(Application $app, Repository $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

    /**
     * @param Job $job
     * @param mixed $models
     */
    public function fire(Job $job, $models)
    {
        $loggerContainerBinding = $this->config->get('logger', 'iverberk.larasearch.logger');
        $logger = $this->app->make($loggerContainerBinding);

        foreach ($models as $model)
        {
            list($class, $id) = explode(':', $model);

            $logger->info('Deleting ' . $class . ' with ID: ' . $id . ' from Elasticsearch');

            $model = new $class;

            $model->deleteDoc($id);
        }

        $job->delete();
    }

}
