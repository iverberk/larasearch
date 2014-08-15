<?php namespace Iverberk\Larasearch\Jobs;

use Mockery as m;
use AspectMock\Test as am;

class ReindexJobTest extends \PHPUnit_Framework_Testcase {

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
        $job = m::mock('Illuminate\Queue\Jobs\Job');
        $models = [
            'Husband:99999'
        ];

        /**
         *
         * Expectation
         *
         */
        $job->shouldReceive('release')->with(60)->once();

        /**
         *
         * Assertion
         *
         */
        with(new ReindexJob)->fire($job, $models);
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
        $job = m::mock('Illuminate\Queue\Jobs\Job');
        $job->shouldReceive('delete')->once();

        /**
         *
         * Assertion
         *
         */
        with(new ReindexJob)->fire($job, $models);

        $husband->verifyInvoked('findOrFail');
    }

} 