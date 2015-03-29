<?php namespace Iverberk\Larasearch\Traits;

use Illuminate\Support\Facades\App;
use Mockery as m;

class TransformableTraitTest extends \PHPUnit_Framework_TestCase {

    protected function tearDown()
    {
        m::close();
    }

    /**
     * @test
     */
    public function it_should_transform()
    {
        /**
         *
         * Set
         *
         */
        $husband = m::mock('Husband')->makePartial();

        /**
         *
         * Expectation
         *
         */
        $husband->shouldReceive('load->toArray')->once()->andReturn('mock');

        $config = m::mock('Iverberk\\Larasearch\\Config');
        $config->shouldReceive('get')->with('/paths\..*/')->once();
        App::shouldReceive('make')
            ->with('iverberk.larasearch.config')
            ->once()
            ->andReturn($config);

        /**
         *
         * Assertion
         *
         */
        $transformed = $husband->transform(true);

        $this->assertEquals('mock', $transformed);
    }

} 