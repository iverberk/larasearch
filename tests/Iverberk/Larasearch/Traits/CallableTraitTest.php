<?php namespace Iverberk\Larasearch\Traits;

use Mockery as m;
use AspectMock\Test as am;

class CallableTraitTest extends \PHPUnit_Framework_TestCase {

    protected function tearDown()
    {
        m::close();
        am::clean();
    }

    /**
     * @test
     */
    public function it_should_boot_callback_trait_and_register_observer()
    {
        $husband = am::double('Husband', ['observe' => null]);

        \Husband::bootCallableTrait();

        $husband->verifyInvoked('observe');
    }

    /**
     * @test
     * @expectedException \Exception
     */
    public function it_should_boot_callback_trait_and_throw_exception()
    {
        \Dummy::bootCallableTrait();
    }

} 