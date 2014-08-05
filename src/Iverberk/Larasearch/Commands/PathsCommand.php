<?php namespace Iverberk\Larasearch\Commands;

use Illuminate\Console\Command;
use Iverberk\Larasearch\Introspect;
use Iverberk\Larasearch\Introspect\Callbacks\PathsCallback;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class PathsCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'larasearch:paths';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Generate paths from Eloquent models';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
        $model = $this->argument('model');
        $relations = $this->option('relations');
        $dryrun = $this->option('dry-run');

        with(new Introspect(new $model))->relationMap($paths = new PathsCallback);

        $this->info(var_export($paths->getPaths()));
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
			array('relations', null, InputOption::VALUE_NONE, 'Include related Eloquent models', null),
			array('dry-run', null, InputOption::VALUE_NONE, 'Show paths only. No config file is written', null),
		);
	}



}