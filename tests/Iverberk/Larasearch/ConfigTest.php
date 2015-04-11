<?php namespace Iverberk\Larasearch;

use Mockery as m;

function constant($const)
{
    return ConfigTest::$functions->constant($const);
}

class ConfigTest extends \PHPUnit_Framework_TestCase {

    public static $functions;

    protected function setup()
    {
        self::$functions = m::mock();
    }

    protected function tearDown()
    {
        m::close();
    }

	/**
	 * @test
	 */
    public function it_should_constructL4()
    {
        $this->forL4();
        $laravel_config = m::mock('Illuminate\\Config\\Repository');
        $config = new Config($laravel_config);
    }

	/**
	 * @test
	 */
    public function it_should_constructL5()
    {
        $this->forL5();
        $laravel_config = m::mock('Illuminate\\Config\\Repository');
        $config = new Config($laravel_config);
    }

	/**
	 * @test
	 */
    public function it_should_construct_with_package_name()
    {
        $this->forL4();
        $laravel_config = m::mock('Illuminate\\Config\\Repository');
        $config = new Config($laravel_config, 'foobarbaz');
    }

	/**
	 * @test
	 */
    public function it_should_get_l4_style()
    {
        $this->forL4();
        $laravel_config = m::mock('Illuminate\\Config\\Repository');
        $laravel_config->shouldReceive('get')
            ->with('larasearch::myconfigsetting', null)
            ->andReturn('myconfigvalue');
        $config = new Config($laravel_config);

        $this->assertEquals($config->get('myconfigsetting'), 'myconfigvalue');
    }

	/**
	 * @test
	 */
    public function it_should_get_l5_style()
    {
        $this->forL5();
        $laravel_config = m::mock('Illuminate\\Config\\Repository');
        $laravel_config->shouldReceive('get')
            ->with('larasearch.myconfigsetting', null)
            ->andReturn('myconfigvalue');
        $config = new Config($laravel_config);

        $this->assertEquals($config->get('myconfigsetting'), 'myconfigvalue');
    }

	/**
	 * @test
	 */
    public function it_should_set_l4_style()
    {
        $this->forL4();
        $laravel_config = m::mock('Illuminate\\Config\\Repository');
        $laravel_config->shouldReceive('set')
            ->with('larasearch::myconfigsetting', 'myconfigvalue')
            ->andReturn(null);
        $config = new Config($laravel_config);

        $this->assertEquals($config->set('myconfigsetting', 'myconfigvalue'), null);
    }

	/**
	 * @test
	 */
    public function it_should_set_l5_style()
    {
        $this->forL5();
        $laravel_config = m::mock('Illuminate\\Config\\Repository');
        $laravel_config->shouldReceive('set')
            ->with('larasearch.myconfigsetting', 'myconfigvalue')
            ->andReturn();
        $config = new Config($laravel_config);

        $this->assertEquals($config->set('myconfigsetting', 'myconfigvalue'), null);
    }

    /**
     * @test
     */
    public function it_should_get_and_set_package_name()
    {
        $this->forL5();
        $laravel_config = m::mock('Illuminate\\Config\\Repository');
        $config = new Config($laravel_config);

        $config->setPackageName($name = 'foo' . microtime(true));
        $this->assertEquals($name, $config->getPackageName());
    }

    protected function forL4()
    {
        self::$functions->shouldReceive('constant')
            ->with('Illuminate\\Foundation\\Application::VERSION')
            ->andReturn('4.2.17');
    }

    protected function forL5()
    {
        self::$functions->shouldReceive('constant')
            ->with('Illuminate\\Foundation\\Application::VERSION')
            ->andReturn('5.0.0');
    }

}
