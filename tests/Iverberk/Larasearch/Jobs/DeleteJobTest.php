<?php namespace Iverberk\Larasearch\Jobs;

use Mockery as m;
use AspectMock\Test as am;

class DeleteJobTest extends \PHPUnit_Framework_TestCase {

    protected function tearDown()
    {
        m::close();
        am::clean();
    }

    /**
     * @test
     */
    public function it_should_fire_job()
    {
        /**
         *
         * Set
         *
         */
	    $app = m::mock('Illuminate\Foundation\Application');
	    $config = m::mock('Iverberk\Larasearch\Config');
	    $logger = m::mock('Monolog\Logger');
	    $husband = am::double('Husband', ['deleteDoc' => true]);

        $job = m::mock('Illuminate\Queue\Jobs\Job');
        $models = [
            'Husband:999'
        ];

        /**
         *
         * Expectation
         *
         */
	    $logger->shouldReceive('info')->with('Deleting Husband with ID: 999 from Elasticsearch');
	    $config->shouldReceive('get')->with('logger', 'iverberk.larasearch.logger')->andReturn('iverberk.larasearch.logger');
	    $app->shouldReceive('make')->with('iverberk.larasearch.logger')->andReturn($logger);
        $job->shouldReceive('delete')->once();

        /**
         *
         * Assertion
         *
         */
        with(new DeleteJob($app, $config))->fire($job, $models);
    }

} 