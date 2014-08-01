<?php namespace Iverberk\Larasearch\Commands;

use Illuminate\Console\Command;
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
        $model = $this->argument('model');

        $this->info("---> Reindexing the ${model} to Elasticsearch\n");

        try
        {
            list($errors, $items) = $model::reindex(
                $this->option('force'),
                $this->option('relations'),
                $this->option('batch'),
                function($batch) {
                    $this->info("---> Batch ${batch}");
                }
            );

            if ($errors)
            {
                $this->error('Errors occured during reindexing!');
                if ($this->confirm('Would you like to see a dump of the erroneous items? [yes|no]'))
                {
                    dd($items);
                }
            }
        }
        catch (\BadMethodCallException $e)
        {
            $this->error('Oops, something went wrong! Did you include the SearchableTrait on the model?');
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
			array('model', InputArgument::REQUIRED, 'Base Eloquent model to use for indexing.'),
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
			array('relations', null, InputOption::VALUE_NONE, 'Reindex related Eloquent models.', null),
            array('batch', null, InputOption::VALUE_OPTIONAL, 'The number of records to index in a single batch.', 750),
            array('force', null, InputOption::VALUE_NONE, 'Overwrite existing indices and documents.', null),
		);
	}

}