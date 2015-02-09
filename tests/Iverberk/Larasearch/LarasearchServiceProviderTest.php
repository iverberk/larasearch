<?php

require __DIR__ . '/../../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

use Illuminate\Support\Facades\App as App;
use Mockery as m;

/**
 * Class LarasearchServiceProviderTest
 */
class LarasearchServiceProviderTest extends PHPUnit_Framework_TestCase {

    protected function tearDown()
    {
        m::close();
    }

    /**
     * @test
     */
    public function it_should_boot()
    {
        /**
         * Set
         */
        $sp = m::mock('Iverberk\Larasearch\LarasearchServiceProvider[package, bootContainerBindings]', ['something']);

        $sp->shouldAllowMockingProtectedMethods();

        /**
         * Expectation
         */
        $sp->shouldReceive('package')
            ->with('iverberk/larasearch')
            ->once();

        $sp->shouldReceive('bootContainerBindings')
            ->once();

        /**
         * Assertion
         */
        $sp->boot();
    }

    /**
     * @test
     */
    public function it_should_boot_container_bindings()
    {
        /**
         * Set
         */
        $sp = m::mock('Iverberk\Larasearch\LarasearchServiceProvider[' .
            'bindProxy, bindIndex, bindLogger, bindElasticsearch, bindQuery, bindResult]', ['something']);
        $sp->shouldAllowMockingProtectedMethods();

        /**
         * Expectation
         */
	    $sp->shouldReceive('bindElasticsearch')->once()->andReturn(true);
	    $sp->shouldReceive('bindLogger')->once()->andReturn(true);
        $sp->shouldReceive('bindProxy')->once()->andReturn(true);
        $sp->shouldReceive('bindIndex')->once()->andReturn(true);
        $sp->shouldReceive('bindQuery')->once()->andReturn(true);
	    $sp->shouldReceive('bindResult')->once()->andReturn(true);

        /**
         * Assertions
         */
        $sp->bootContainerBindings();
    }

    /**
     * @test
     */
    public function it_should_bind_elasticsearch()
    {
        /**
         * Set
         */
        $config = m::mock();
        $app = m::mock('LaravelApp');
        $sp = m::mock('Iverberk\Larasearch\LarasearchServiceProvider[bindElasticsearch]', [$app]);

        /**
         * Expectation
         */
        $config->shouldReceive('get')
            ->with('larasearch::elasticsearch.params')
            ->once()
            ->andReturn([]);

        $app->shouldReceive('make')
            ->with('config')
            ->once()
            ->andReturn($config);

        $app->shouldReceive('singleton')
            ->once()
            ->andReturnUsing(
                function ($name, $closure) use ($app)
                {
                    assertEquals('Elasticsearch', $name);
                    assertInstanceOf('Elasticsearch\Client', $closure($app));
                }
            );

        $sp->bindElasticsearch();
    }

	/**
	 * @test
	 */
	public function it_should_bind_logger()
	{
		/**
		 * Set
		 */
		$app = m::mock('LaravelApp');
		$sp = m::mock('Iverberk\Larasearch\LarasearchServiceProvider[bindLogger]', [$app]);

		/**
		 * Expectation
		 */
		$app->shouldReceive('singleton')
			->once()
			->andReturnUsing(
				function ($name, $closure) use ($app)
				{
					assertEquals('iverberk.larasearch.logger', $name);
					assertInstanceOf('Monolog\Logger', $closure($app));
				}
			);

		$sp->bindLogger();
	}

    /**
     * @test
     */
    public function it_should_bind_index()
    {
        /**
         * Set
         */
        App::shouldReceive('make')
            ->with('iverberk.larasearch.index', Mockery::any())
            ->once()
            ->andReturn('mock');

        App::shouldReceive('make')
            ->with('Elasticsearch')
            ->twice()
            ->andReturn('mock');

        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('getTable')
            ->once()
            ->andReturn('mockType');
        $app = m::mock('LaravelApp');
        $proxy = m::mock('Iverberk\Larasearch\Proxy', [$model]);
        $sp = m::mock('Iverberk\Larasearch\LarasearchServiceProvider[bindIndex]', [$app]);

        /**
         * Expectation
         */
        $app->shouldReceive('bind')
            ->once()
            ->andReturnUsing(
                function ($name, $closure) use ($app, $proxy)
                {
                    assertEquals('iverberk.larasearch.index', $name);
                    assertInstanceOf('Iverberk\Larasearch\Index',
                        $closure($app, ['proxy' => $proxy, 'name' => 'name']));
                }
            );


        /**
         * Assertion
         */
        $sp->bindIndex();
    }

    /**
     * @test
     */
    public function it_should_bind_query()
    {
        /**
         * Set
         */
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('getTable')
            ->once()
            ->andReturn('mockType');
        $app = m::mock('LaravelApp');
        $proxy = m::mock('Iverberk\Larasearch\Proxy', [$model]);
        $sp = m::mock('Iverberk\Larasearch\LarasearchServiceProvider[bindQuery]', [$app]);

        /**
         * Expectation
         */
        $app->shouldReceive('bind')
            ->once()
            ->andReturnUsing(
                function ($name, $closure) use ($app, $proxy)
                {
                    assertEquals('iverberk.larasearch.query', $name);
                    assertInstanceOf('Iverberk\Larasearch\Query',
                        $closure($app, ['proxy' => $proxy, 'term' => 'term', 'options' => []]));
                }
            );

        /**
         * Assertion
         */
        $sp->bindQuery();
    }

    /**
     * @test
     */
    public function it_should_bind_proxy()
    {
        /**
         * Set
         */
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('getTable')
            ->once()
            ->andReturn('mockType');
        $app = m::mock('LaravelApp');
        $sp = m::mock('Iverberk\Larasearch\LarasearchServiceProvider[bindProxy]', [$app]);

        /**
         * Expectation
         */
        $app->shouldReceive('bind')
            ->once()
            ->andReturnUsing(
                function ($name, $closure) use ($app, $model)
                {
                    assertEquals('iverberk.larasearch.proxy', $name);
                    assertInstanceOf('Iverberk\Larasearch\Proxy',
                        $closure($app, $model));
                }
            );


        /**
         * Assertion
         */
        $sp->bindProxy();
    }

	/**
	 * @test
	 */
	public function it_should_bind_result()
	{
		/**
		 * Set
		 */
		$app = m::mock('LaravelApp');
		$sp = m::mock('Iverberk\Larasearch\LarasearchServiceProvider[bindResult]', [$app]);

		/**
		 * Expectation
		 */
		$app->shouldReceive('bind')
			->once()
			->andReturnUsing(
				function ($name, $closure) use ($app)
				{
					assertEquals('iverberk.larasearch.response.result', $name);
					assertInstanceOf('Iverberk\Larasearch\Response\Result',
						$closure($app, []));
				}
			);

		/**
		 * Assertion
		 */
		$sp->bindResult();
	}

    /**
     * @test
     */
    public function it_should_register_commands()
    {
        /**
         * Set
         */
        $app = m::mock('Illuminate\Container\Container');
        $sp = m::mock('Iverberk\Larasearch\LarasearchServiceProvider[registerCommands, commands]', [$app]);

        /**
         * Expectation
         */
        $app->shouldReceive('offsetSet')->andReturn(true);
        $app->shouldReceive('offsetGet')->andReturn(true);

        $app->shouldReceive('share')
            ->once()
            ->andReturnUsing(function ($closure) use ($app)
            {
                assertInstanceOf('Iverberk\Larasearch\Commands\ReindexCommand', $closure($app));
            });

        $app->shouldReceive('share')
            ->once()
            ->andReturnUsing(function ($closure) use ($app)
            {
                assertInstanceOf('Iverberk\Larasearch\Commands\PathsCommand', $closure($app));
            });

        $sp->shouldReceive('commands')
            ->with('iverberk.larasearch.commands.reindex')
            ->once()
            ->andReturn(true);

        $sp->shouldReceive('commands')
            ->with('iverberk.larasearch.commands.paths')
            ->once()
            ->andReturn(true);

        /**
         * Assertion
         */
        $sp->registerCommands();
    }

    /**
     * @test
     */
    public function it_should_provide_services()
    {
        /**
         * Set
         */
        $app = m::mock('LaravelApp');

        /**
         * Assertion
         */
        $sp = new Iverberk\Larasearch\LarasearchServiceProvider($app);
        assertEquals([], $sp->provides());
    }

}