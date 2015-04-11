<?php namespace Iverberk\Larasearch;

use Husband;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Queue;
use Mockery as m;
use AspectMock\Test as am;

class ObserverTest extends \PHPUnit_Framework_TestCase {

    protected function tearDown()
    {
        m::close();
        am::clean();
    }

	/**
	 * @test
	 */
	public function it_should_not_reindex_on_model_save()
	{
		/**
		 *
		 * Expectation
		 *
		 */
		$queue = am::double('Illuminate\Support\Facades\Queue', ['push' => null]);

		$husband = m::mock('Husband');
		$husband->shouldReceive('shouldIndex')->andReturn(false);

		/**
		 *
		 *
		 * Assertion
		 *
		 */
		with(new Observer)->saved($husband);

		$queue->verifyNeverInvoked('push');
	}

    /**
     * @test
     */
    public function it_should_reindex_on_model_save()
    {
        /**
         *
         * Expectation
         *
         */
        Facade::clearResolvedInstances();

        $proxy = m::mock('Iverberk\Larasearch\Proxy');
	    $proxy->shouldReceive('shouldIndex')->andReturn(true);

	    App::shouldReceive('make')
		    ->with('iverberk.larasearch.proxy', m::type('Illuminate\Database\Eloquent\Model'))
		    ->andReturn($proxy);

        $config = m::mock('Iverberk\Larasearch\Config');
        $config->shouldReceive('get')
            ->with('reversedPaths.Husband', [])
            ->once()
            ->andReturn(['', 'wife', 'children', 'children.toys']);
	    App::shouldReceive('make')
		    ->with('iverberk.larasearch.config')
            ->andReturn($config);

	    Queue::shouldReceive('push')
            ->with('Iverberk\Larasearch\Jobs\ReindexJob', [
	            'Husband:2',
	            'Wife:2',
	            'Child:2',
	            'Toy:2'
            ])->once();

        /**
         *
         *
         * Assertion
         *
         */
	    $husband = \Husband::find(2);

        with(new Observer)->saved($husband);

	    /**
	     *
	     * Expectation
	     *
	     */
	    Facade::clearResolvedInstances();

	    $proxy = m::mock('Iverberk\Larasearch\Proxy');
	    $proxy->shouldReceive('shouldIndex')->andReturn(true);

	    App::shouldReceive('make')
		    ->with('iverberk.larasearch.proxy', m::type('Illuminate\Database\Eloquent\Model'))
		    ->andReturn($proxy);

	    $config->shouldReceive('get')
		    ->with('reversedPaths.Toy', [])
		    ->once()
		    ->andReturn(['', 'children', 'children.mother.husband', 'children.mother']);
	    App::shouldReceive('make')
		    ->with('iverberk.larasearch.config')
            ->andReturn($config);

	    Queue::shouldReceive('push')
		    ->with('Iverberk\Larasearch\Jobs\ReindexJob', [
			    'Toy:2',
			    'Child:8',
			    'Child:2',
			    'Husband:8',
			    'Husband:2',
			    'Wife:8',
			    'Wife:2'
		    ])->once();

	    /**
	     *
	     *
	     * Assertion
	     *
	     */
	    $toy = \Toy::find(2);

	    with(new Observer)->saved($toy);
    }

    /**
     * @test
     */
    public function it_should_reindex_on_model_delete()
    {
	    /**
	     *
	     * Expectation
	     *
	     */
	    Facade::clearResolvedInstances();

	    Queue::shouldReceive('push')
		    ->with('Iverberk\Larasearch\Jobs\DeleteJob', [ 'Husband:2' ])
		    ->once();

	    Queue::shouldReceive('push')
		    ->with('Iverberk\Larasearch\Jobs\ReindexJob', [ 'Wife:2', 'Child:2', 'Toy:2' ])
		    ->once();

        $config = m::mock('Iverberk\\Larasearch\\Config');
	    $config->shouldReceive('get')
		    ->with('/^reversedPaths\..*$/', [])
		    ->once()
		    ->andReturn(['', 'wife', 'children', 'children.toys']);
        App::shouldReceive('make')
            ->with('iverberk.larasearch.config')
            ->andReturn($config);
        $husband = \Husband::find(2);

        with(new Observer)->deleted($husband);
    }

} 