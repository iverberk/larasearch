<?php namespace Iverberk\Larasearch;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Mockery as m;

function base_path($path = null)
{
    return LarasearchServiceProviderTest::$functions->base_path($path);
}

/**
 * Class LarasearchServiceProviderTest
 */
class LarasearchServiceProviderTest extends \PHPUnit_Framework_TestCase {

    public static $functions;
    protected static $providers_real_path;

    protected function setup()
    {
        self::$functions = m::mock();
        self::$functions->shouldReceive('base_path')->andReturn('');
        self::$providers_real_path = realpath(__DIR__ . '/../../../src/Iverberk/Larasearch');
    }

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
        $sp = m::mock('Iverberk\Larasearch\LarasearchServiceProvider[package, bootContainerBindings, publishes]', ['something']);
        $sp->shouldAllowMockingProtectedMethods();

        /**
         * Expectation
         */
        $sp->shouldReceive('publishes')
            ->with([
                self::$providers_real_path . '/../../config/larasearch.php' => base_path('config/larasearch.php'),
            ], 'config')
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
            ->with('elasticsearch.params')
            ->once()
            ->andReturn([]);

        $app->shouldReceive('make')
            ->with('iverberk.larasearch.config')
            ->once()
            ->andReturn($config);

        $app->shouldReceive('singleton')
            ->once()
            ->andReturnUsing(
                function ($name, $closure) use ($app)
                {
                    $this->assertEquals('Elasticsearch', $name);
                    $this->assertInstanceOf('Elasticsearch\Client', $closure($app));
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
                    $this->assertEquals('iverberk.larasearch.logger', $name);
                    $this->assertInstanceOf('Monolog\Logger', $closure($app));
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
        App::clearResolvedInstances();
        Config::clearResolvedInstances();

        App::shouldReceive('make')
            ->with('iverberk.larasearch.index', m::any())
            ->once()
            ->andReturn('mock');

        App::shouldReceive('make')
            ->with('Elasticsearch')
            ->twice()
            ->andReturn('mock');

        Config::shouldReceive('get')
            ->with('elasticsearch.index_prefix', '')
            ->andReturn('');

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
                    $this->assertEquals('iverberk.larasearch.index', $name);
                    $this->assertInstanceOf('Iverberk\Larasearch\Index',
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
                    $this->assertEquals('iverberk.larasearch.query', $name);
                    $this->assertInstanceOf('Iverberk\Larasearch\Query',
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
                    $this->assertEquals('iverberk.larasearch.proxy', $name);
                    $this->assertInstanceOf('Iverberk\Larasearch\Proxy',
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
                    $this->assertEquals('iverberk.larasearch.response.result', $name);
                    $this->assertInstanceOf('Iverberk\Larasearch\Response\Result',
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
        $sp = m::mock('Iverberk\Larasearch\LarasearchServiceProvider[commands, mergeConfigFrom]', [$app]);
        $sp->shouldAllowMockingProtectedMethods();

        /**
         * Expectation
         */
        $app->shouldReceive('offsetSet')->andReturn(true);
        $app->shouldReceive('offsetGet')->andReturn(true);

        $app->shouldReceive('share')
            ->once()
            ->andReturnUsing(function ($closure) use ($app)
            {
                $this->assertInstanceOf('Iverberk\Larasearch\Commands\ReindexCommand', $closure($app));
            });

        $app->shouldReceive('share')
            ->once()
            ->andReturnUsing(function ($closure) use ($app)
            {
                $this->assertInstanceOf('Iverberk\Larasearch\Commands\PathsCommand', $closure($app));
            });

        $sp->shouldReceive('commands')
            ->with('iverberk.larasearch.commands.reindex')
            ->once()
            ->andReturn(true);

        $sp->shouldReceive('commands')
            ->with('iverberk.larasearch.commands.paths')
            ->once()
            ->andReturn(true);

        $sp->shouldReceive('mergeConfigFrom')
            ->with(self::$providers_real_path . '/../../config/config.php', 'larasearch')
            ->once();

        /**
         * Assertion
         */
        $sp->register();
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
        $sp = new LarasearchServiceProvider($app);
        $this->assertEquals([], $sp->provides());
    }

}
