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
        $job->shouldReceive('delete')->once();

        /**
         *
         * Assertion
         *
         */
        with(new DeleteJob)->fire($job, $models);
    }

} 