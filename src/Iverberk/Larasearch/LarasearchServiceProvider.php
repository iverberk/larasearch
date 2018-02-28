<?php namespace Iverberk\Larasearch;

use Monolog\Logger;
use Elasticsearch\Client;
use Monolog\Handler\NullHandler;
use Illuminate\Support\ServiceProvider;
use Iverberk\Larasearch\Response\Result;
use Iverberk\Larasearch\Commands\PathsCommand;
use Iverberk\Larasearch\Commands\ReindexCommand;

class LarasearchServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    public function boot()
    {
        $this->bootContainerBindings();

        $this->publishes([
            __DIR__ . '/../../config/larasearch.php' => base_path('config/larasearch.php'),
        ], 'config');

        if ($this->app->runningInConsole()) {
            $this->commands([
                ReindexCommand::class,
                PathsCommand::class,
            ]);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        if (file_exists(base_path('config/larasearch.php')))
        {
            $this->mergeConfigFrom(base_path('config/larasearch.php'), 'larasearch');
        }
        else
        {
            $this->mergeConfigFrom(__DIR__ . '/../../config/larasearch.php', 'larasearch');
        }
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
            return new Logger('larasearch', [new NullHandler()]);
        });
    }

    /**
     * Bind the Elasticsearch client to the container
     */
    protected function bindElasticsearch()
    {
        $this->app->singleton('Elasticsearch', function ($app)
        {
            return new Client(\Illuminate\Support\Facades\Config::get('larasearch.elasticsearch.params'));
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
        $this->app->bind('iverberk.larasearch.proxy', function ($app, $param)
        {
            return new Proxy($param['model']);
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
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
