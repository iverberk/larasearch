<?php namespace Iverberk\Larasearch\Commands;

use Guzzle\Common\Exception\RuntimeException;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use Iverberk\Larasearch\Utils;
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
	 * @var array
	 */
	private $relationClassMethods = [];

	/**
	 * @var array
	 */
	private $relatedModels = [];

	/**
	 * @var array
	 */
	private $paths = [];

	/**
	 * @var array
	 */
	private $reversedPaths = [];

	/**
	 * Scan directories for Eloquent models that use the SearchableTrait.
	 * Generate paths for all these models so that we can (re)index these
	 * models.
	 *
	 * @return void
	 */
	public function fire()
	{
		$models = $this->argument('model');

		foreach ($models as $model)
		{
			$this->compilePaths(new $model);
		}

		if ($directories = $this->option('dir'))
		{
			$directoryModels = array_diff(Utils::findSearchableModels($directories), $models);

			foreach ($directoryModels as $model)
			{
				// Find paths for related models
				$this->compilePaths(new $model);
			}
		}

		if ( ! empty($models) || ! empty($directoryModels))
		{
			$this->writeConfig();
		}
		else
		{
			$this->info('No models found.');
		}
	}

	/**
	 * @return array
	 */
	public function getPaths()
	{
		return $this->paths;
	}

	/**
	 * @return array
	 */
	public function getReversedPaths()
	{
		return $this->reversedPaths;
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			array('model', InputOption::VALUE_OPTIONAL, 'Eloquent model to find paths for', null)
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
			array('dir', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Directory to scan for searchable models', null, ''),
			array('relations', null, InputOption::VALUE_NONE, 'Include related Eloquent models', null),
			array('write-config', null, InputOption::VALUE_NONE, 'Include the compiled paths in the package configuration', null),
		);
	}

	/**
	 * Inspect all relations and build a (reverse) path for every relation.
	 * This information is used to quickly determine which relations need to
	 * be eager loaded on a model when (re)indexing. It also defines the document
	 * structure for Elasticsearch.
	 *
	 * @param \Illuminate\Database\Eloquent\Model
	 * @param string $ancestor
	 * @param array  $path
	 * @param array  $reversedPath
	 * @param null   $start
	 */
	protected function compilePaths(Model $model, $ancestor = null, $path = [], $reversedPath = [], $start = null)
	{
		// Initialize some variables if this is the first call
		if ($ancestor == null) $ancestor = $model;
		if ($start == null) $start = $model;

		$modelClass = get_class($model);

		// Initialize the found relations to an empty array
		$relations = [];

		if ($this->option('relations'))
		{
			// Find all related models
			$relatedModels = $this->getRelatedModels($model);

			foreach ($relatedModels as $related)
			{
				$newPath = $path;
				$newPath[] = $related['method']->name;

				// Check if we need to recurse for this related model
				if (!$related['model'] instanceof $ancestor &&
					!$related['model'] instanceof $start &&
					$this->checkDocHints($related['method']->getDocComment(), $start)
				)
				{
					// Get the relations of the related model here, so
					// that we can build a reversed path for this relation
					$this->getRelatedModels($related['model']);

                    $newReversedPath = $reversedPath;

                    // Check if a reciprocal relation is found back to the original model
                    if ( ! isset($this->relationClassMethods[get_class($related['model'])][$modelClass]))
                    {
                        // Check if we are possibly dealing with a polymorphic relation (reference to itself)
                        if (array_key_exists(get_class($related['model']), $this->relationClassMethods[get_class($related['model'])]))
                        {
                            $model = get_class($related['model']);
                            $newReversedPath[] = $this->relationClassMethods[$model][$model];
                        }
                        else
                        {
                            throw new \RuntimeException("Reciprocal relation not found for model '" . get_class($related['model']) . "'");
                        }
                    }
                    else
                    {
                        $newReversedPath[] = $this->relationClassMethods[get_class($related['model'])][$modelClass];
                    }

					// Add this relation
					$relations[] = $related;

					$this->reversedPaths[$modelClass][] = implode('.', array_reverse($reversedPath));
					$this->reversedPaths[$modelClass] = array_values(array_filter(array_unique($this->reversedPaths[$modelClass])));

					$this->compilePaths($related['model'], $model, $newPath, $newReversedPath, $start);
				}
			}
		}

		// Found no more relations for this model so build the final path
		// and add the last inverse path segment
		if (empty($relations))
		{

			if ( ! empty($path))
			{
				$this->paths[get_class($start)][] = implode('.', $path);
			}
			else
			{
				$this->paths[get_class($start)] = [];
			}

			$this->reversedPaths[$modelClass][] = implode('.', array_reverse($reversedPath));
			$this->reversedPaths[$modelClass] = array_values(array_filter(array_unique($this->reversedPaths[$modelClass])));
		}
	}

	/**
	 * Inspect the relation method annotations to see if we need to follow the relation
	 *
	 * @param string $docComment
	 * @param        $model
	 * @return bool
	 */
	protected function checkDocHints($docComment, $model)
	{
		// Check if we never follow this relation
		if (preg_match('/@follow\s+NEVER/', $docComment)) return false;

		// Check if we follow the relation from the 'base' model
		if (preg_match('/@follow\s+UNLESS\s+' . str_replace('\\', '\\\\', get_class($model)) . '\b/', $docComment))
		{
			return false;
		}

		// We follow the relation
		return true;
	}

	/**
	 * Find related models from a base model
	 *
	 * @param $model
	 * @return array
	 */
	protected function getRelatedModels(Model $model)
	{
		// Store the class name
		$modelClass = get_class($model);

		// Check if we already know the related models for this model
		if (!isset($this->relatedModels[$modelClass]))
		{
			$relatedModels = [];

			$methods = with(new \ReflectionClass($model))->getMethods();

			// Iterate all class methods
			foreach ($methods as $method)
			{
				// Check if this method returns an Eloquent relation
				if ($method->class == $modelClass &&
					preg_match('/@return\s+\\\\Illuminate\\\\Database\\\\Eloquent\\\\Relations/', $method->getDocComment())
				)
				{
					// Get the method name, so that we can call it on the model
					$relationMethod = $method->name;

					// Find the relation
					$relation = $model->$relationMethod();

					if ($relation instanceof Relation)
					{
						// Find the related model
						$related = $relation->getRelated();

						// Store the method to help build the inverse path
						$this->relationClassMethods[$modelClass][get_class($related)] = $relationMethod;

						$relatedModels[] = ['model' => $related, 'method' => $method];
					}
				}
			}

			// Cache related models for this model
			$this->relatedModels[$modelClass] = $relatedModels;

			// Return the related models
			return $relatedModels;
		}
		else
		{
			// Return from cache
			return $this->relatedModels[$modelClass];
		}
	}

	/**
	 * Write the paths config to a file or to standard output
	 *
	 * @return void
	 */
	private function writeConfig()
	{
		if ($this->option('write-config'))
		{
			$configDir = app_path() . '/config/packages/iverberk/larasearch';

			if (!File::exists($configDir))
			{
				if ($this->confirm('It appears that you have not yet published the larasearch config. Would you like to do this now?', false))
				{
					$this->call('config:publish', ['package' => 'iverberk/larasearch']);
				}
				else
				{
					return;
				}
			}

			File::put("${configDir}/paths.json", json_encode(['paths' => $this->paths, 'reversedPaths' => $this->reversedPaths], JSON_PRETTY_PRINT));

			$this->info('Paths file written to local package configuration');
		}
		else
		{
			$this->info(json_encode(['paths' => $this->paths, 'reversedPaths' => $this->reversedPaths], JSON_PRETTY_PRINT));
		}
	}

}
