<?php

namespace Iverberk\Larasearch\Commands;

use Iverberk\Larasearch\Utils;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Database\Eloquent\Model;

class ReindexCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'larasearch:reindex
                            {model? : Eloquent model to reindex}
                            {--relations : Reindex related Eloquent models}
                            {--mapping= : A file containing custom mappings}
                            {--dir=* : Directory to scan for searchable models}
                            {--batch=750 : The number of records to index in a single batch}
                            {--force : Overwrite existing indices and documents}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reindex Eloquent models to Elasticsearch.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $directoryModels = [];
        $models = $this->argument('model');

        foreach ($models as $model) {
            $instance = $this->getModelInstance($model);
            $this->reindexModel($instance);
        }

        if ($directories = $this->option('dir')) {
            $directoryModels = array_diff(Utils::findSearchableModels($directories), $models);

            foreach ($directoryModels as $model) {
                $instance = $this->getModelInstance($model);
                $this->reindexModel($instance);
            }
        }

        if (empty($models) && empty($directoryModels)) {
            $this->info('No models found.');
        }
    }

    /**
     * Reindex a model to Elasticsearch
     *
     * @param Model $model
     */
    protected function reindexModel(Model $model)
    {
        $mapping = $this->option('mapping') ? json_decode(File::get($this->option('mapping')), true) : null;

        $this->info('---> Reindexing ' . get_class($model));

        $model->reindex(
            $this->option('relations'),
            $this->option('batch'),
            $mapping,
            function ($batch) {
                $this->info("* Batch ${batch}");
            }
        );
    }

    /**
     * Simple method to create instances of classes on the fly
     * It's primarily here to enable unit-testing
     *
     * @param string $model
     */
    protected function getModelInstance($model)
    {
        return new $model;
    }

}
