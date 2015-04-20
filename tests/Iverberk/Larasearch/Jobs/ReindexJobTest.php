<?php namespace Iverberk\Larasearch\Jobs;

use Illuminate\Support\Facades\App;
use Mockery as m;
use AspectMock\Test as am;
use Mockery;

class ReindexJobTest extends \PHPUnit_Framework_TestCase {

    protected function tearDown()
    {
        m::close();
        am::clean();
    }

    /**
     * @test
     */
    public function it_should_fire_job_with_unresolvable_models()
    {
        /**
         *
         * Set
         *
         */
        App::shouldReceive('make')
            ->with('iverberk.larasearch.proxy', Mockery::any())
            ->once()
            ->andReturn('mock');

        $app = m::mock('Illuminate\Foundation\Application');
        $config = m::mock('Illuminate\Config\Repository');
        $logger = m::mock('Monolog\Logger');
        $job = m::mock('Illuminate\Queue\Jobs\Job');
        $models = [
            'Husband:99999'
        ];

        /**
         *
         * Expectation
         *
         */
        $logger->shouldReceive('info')->with('Indexing Husband with ID: 99999');
        $logger->shouldReceive('error')->with('Indexing Husband with ID: 99999 failed: No query results for model [Husband].');
        $config->shouldReceive('get')->with('larasearch.logger')->andReturn('iverberk.larasearch.logger');
        $app->shouldReceive('make')->with('iverberk.larasearch.logger')->andReturn($logger);
        $job->shouldReceive('delete')->once();
        $job->shouldReceive('release')->with(60)->once();

        /**
         *
         * Assertion
         *
         */
        with(new ReindexJob($app, $config))->fire($job, $models);
    }

    /**
     * @test
     */
    public function it_should_fire_job_with_resolvable_models()
    {
        /**
         *
         * Set
         *
         */
        $app = m::mock('Illuminate\Foundation\Application');
        $config = m::mock('Illuminate\Config\Repository');
        $logger = m::mock('Monolog\Logger');
        $model = m::mock('Husband');
        $model->shouldReceive('refreshDoc')->with($model)->once();
        $husband = am::double('Husband', ['findOrFail' => $model]);
        $models = [
            'Husband:999'
        ];

        /**
         *
         * Expectation
         *
         */
        $logger->shouldReceive('info')->with('Indexing Husband with ID: 999');
        $config->shouldReceive('get')->with('larasearch.logger')->andReturn('iverberk.larasearch.logger');
        $app->shouldReceive('make')->with('iverberk.larasearch.logger')->andReturn($logger);
        $job = m::mock('Illuminate\Queue\Jobs\Job');
        $job->shouldReceive('delete')->once();

        /**
         *
         * Assertion
         *
         */
        with(new ReindexJob($app, $config))->fire($job, $models);

        // $husband->verifyInvoked('findOrFail');
    }

}
