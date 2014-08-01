<?php namespace Iverberk\Larasearch;

use Elasticsearch\Client;
use Illuminate\Support\ServiceProvider;

class LarasearchServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
        $this->package('Iverberk/larasearch');

        $this->app->singleton('Elasticsearch', function()
        {
            return new Client(\Config::get('larasearch::elasticsearch.params'));
        });

        $this->app->bind('Index', function($app)
        {
            return new Index;
        });

        $this->app->bind('Proxy', function($app, $model)
        {
            return new Proxy($model);
        });

        $this->app->bind('Index', function($app, $name)
        {
            return new Index($name);
        });

        $this->app->bind('Query', function($app, $params)
        {
            return new Query($params['proxy'], $params['term'], $params['options']);
        });

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
