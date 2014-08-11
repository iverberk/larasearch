<?php namespace Iverberk\Larasearch\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Iverberk\Larasearch\Proxy;
use Iverberk\Larasearch\Utils;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class ReindexCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'larasearch:reindex';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Reindex Eloquent models to Elasticsearch.';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
        if ($models = $this->argument('model'))
        {
            foreach($models as $model)
            {
                $this->reindexModel(new $model);
            }
        }
        elseif ($directories = $this->option('dir'))
        {
            $models = Utils::findSearchableModels($directories);

            if (empty($models))
            {
                $this->info("No models found that use the Searchable trait. Nothing to do!");

                return;
            }

            foreach ($models as $model)
            {
                // Reindex model
                $this->reindexModel(new $model);
            }
        }
        else
        {
            $this->error("No directories or model specified. Nothing to do!");

            return;
        }
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
            array('model', InputOption::VALUE_OPTIONAL, 'Eloquent model to reindex', null),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			array('relations', null, InputOption::VALUE_NONE, 'Reindex related Eloquent models', null),
			array('mapping', null, InputOption::VALUE_REQUIRED, 'A file containing custom mappings', null),
			array('dir', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Directory to scan for searchable models', null),
            array('batch', null, InputOption::VALUE_OPTIONAL, 'The number of records to index in a single batch', 750),
            array('force', null, InputOption::VALUE_NONE, 'Overwrite existing indices and documents', null),
		);
	}

    /**
     * Reindex a model to Elasticsearch
     *
     * @param $model
     */
    private function reindexModel($model)
    {
        $mapping = $this->option('mapping') ? json_decode(File::get($this->option('mapping')), true) : null;

        $this->info('---> Reindexing ' . get_class($model));

        with(new Proxy($model))->reindex(
            $this->option('force'),
            $this->option('relations'),
            $this->option('batch'),
            $mapping,
            function($batch) {
                $this->info("* Batch ${batch}");
            }
        );
    }

}