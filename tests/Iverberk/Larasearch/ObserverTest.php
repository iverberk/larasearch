<?php namespace Iverberk\Larasearch;

use Husband;
use Illuminate\Support\Facades\Config;
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
    public function it_should_reindex_on_model_save()
    {
        /**
         *
         * Expectation
         *
         */
        Facade::clearResolvedInstances();
        Config::shouldReceive('get')
            ->with('/^larasearch::reversedPaths\..*$/', array())
            ->once()
            ->andReturn(['', 'wife', 'children', 'children.toys']);

        Queue::shouldReceive('push')
            ->with('Iverberk\Larasearch\Jobs\ReindexJob', [ 'Husband:2', 'Wife:2', 'Child:2', 'Toy:2' ])
            ->once();

        /**
         *
         *
         * Assertion
         *
         */
        $husband = \Husband::find(2);

        with(new Observer)->saved($husband);
    }

    /**
     * @test
     */
    public function it_shoud_reindex_on_model_delete()
    {
	    /**
	     *
	     * Expectation
	     *
	     */
	    Facade::clearResolvedInstances();

	    Queue::shouldReceive('push')
		    ->with('Iverberk\Larasearch\Jobs\DeleteJob', [ 'Husband:2', 'Wife:2', 'Child:2', 'Toy:2' ])
		    ->once();

	    Config::shouldReceive('get')
		    ->with('/^larasearch::reversedPaths\..*$/', array())
		    ->once()
		    ->andReturn(['', 'wife', 'children', 'children.toys']);

        $husband = \Husband::find(2);

        with(new Observer)->deleted($husband);
    }

} 