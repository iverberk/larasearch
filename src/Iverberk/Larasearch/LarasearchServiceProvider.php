<?php namespace Iverberk\Larasearch;

use Elasticsearch\Client;
use Illuminate\Support\ServiceProvider;
use Iverberk\Larasearch\Commands\PathsCommand;
use Iverberk\Larasearch\Commands\ReindexCommand;

class LarasearchServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

    public function boot()
    {
        $this->package('iverberk/larasearch');

        $this->bootContainerBindings();
    }

    /**
     * Boot the container bindings.
     *
     * @return void
     */
    protected function bootContainerBindings()
    {
        $this->app->singleton('Elasticsearch', function()
        {
            return new Client(\Config::get('larasearch::elasticsearch.params'));
        });

        $this->app->bind('iverberk.larasearch.index', function($app, $params)
        {
            $name = isset($params['name']) ? $params['name'] : '';

            return new Index($params['proxy'], $name);
        });

        $this->app->bind('iverberk.larasearch.query', function($app, $params)
        {
            return new Query($params['proxy'], $params['term'], $params['options']);
        });

        $this->app->bind('iverberk.larasearch.proxy', function($app, $model)
        {
            return new Proxy($model);
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
        $this->app['iverberk.larasearch.commands.reindex'] = $this->app->share(function ($app) {
            return new ReindexCommand();
        });

        $this->app['iverberk.larasearch.commands.paths'] = $this->app->share(function ($app) {
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

}
