<?php namespace Iverberk\Larasearch;

use Elasticsearch\Client;
use Illuminate\Support\ServiceProvider;
use Iverberk\Larasearch\Commands\PathsCommand;
use Iverberk\Larasearch\Commands\ReindexCommand;
use Iverberk\Larasearch\Response\Result;
use Monolog\Handler\NullHandler;
use Monolog\Logger;
use ReflectionClass;

class LarasearchServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	public function boot()
	{
        $this->publishes([
            $this->guessPackagePath() . '/config/config.php' => config_path('larasearch.php'),
        ]);
        
		$this->bootContainerBindings();
	}

	/**
	 * Boot the container bindings.
	 *
	 * @return void
	 */
	public function bootContainerBindings()
	{
		$this->bindElasticsearch();
		$this->bindLogger();
		$this->bindIndex();
		$this->bindQuery();
		$this->bindProxy();
		$this->bindResult();
	}

	/**
	 * Bind a Larasearch log handler to the container
	 */
	protected function bindLogger()
	{
		$this->app->singleton('iverberk.larasearch.logger', function ($app)
		{
			return new Logger('larasearch', [ new NullHandler() ]);
		});
	}

	/**
	 * Bind the Elasticsearch client to the container
	 */
	protected function bindElasticsearch()
	{
		$this->app->singleton('Elasticsearch', function ($app)
		{
			return new Client($app->make('config')->get('larasearch.elasticsearch.params'));
		});
	}

	/**
	 * Bind the Larasearch index to the container
	 */
	protected function bindIndex()
	{
		$this->app->bind('iverberk.larasearch.index', function ($app, $params)
		{
			$name = isset($params['name']) ? $params['name'] : '';

			return new Index($params['proxy'], $name);
		});
	}

	/**
	 * Bind the Larasearch Query to the container
	 */
	protected function bindQuery()
	{
		$this->app->bind('iverberk.larasearch.query', function ($app, $params)
		{
			return new Query($params['proxy'], $params['term'], $params['options']);
		});
	}

	/**
	 * Bind the Larasearch proxy to the container
	 */
	protected function bindProxy()
	{
		$this->app->bind('iverberk.larasearch.proxy', function ($app, $model)
		{
			return new Proxy($model);
		});
	}

	/**
	 * Bind the Larasearch result to the container
	 */
	protected function bindResult()
	{
		$this->app->bind('iverberk.larasearch.response.result', function ($app, array $hit)
		{
			return new Result($hit);
		});
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerCommands();
	}

	/**
	 * Register the commands.
	 *
	 * @return void
	 */
	protected function registerCommands()
	{
		$this->app['iverberk.larasearch.commands.reindex'] = $this->app->share(function ($app)
		{
			return new ReindexCommand();
		});

		$this->app['iverberk.larasearch.commands.paths'] = $this->app->share(function ($app)
		{
			return new PathsCommand();
		});

		$this->commands('iverberk.larasearch.commands.reindex');
		$this->commands('iverberk.larasearch.commands.paths');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

    /**
     * Guess real path of the package.
     *
     * @return string
     */
    public function guessPackagePath()
    {
        $path = (new ReflectionClass($this))->getFileName();

        return realpath(dirname($path).'/../..');
    }
}
