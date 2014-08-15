<?php namespace Iverberk\Larasearch\Commands;

use Symfony\Component\Console\Input\InputOption;
use Mockery as m;

class ReindexCommandTest extends \PHPUnit_Framework_TestCase {

    /**
     *
     */
    protected function tearDown()
    {
        m::close();
    }

    /**
     * @test
     */
    public function it_should_get_options()
    {
        /**
         *
         * Set
         *
         **/
        $command = m::mock('Iverberk\Larasearch\Commands\ReindexCommand');
        $options = array(
            array('relations', null, InputOption::VALUE_NONE, 'Reindex related Eloquent models', null),
			array('mapping', null, InputOption::VALUE_REQUIRED, 'A file containing custom mappings', null),
			array('dir', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Directory to scan for searchable models', null),
            array('batch', null, InputOption::VALUE_OPTIONAL, 'The number of records to index in a single batch', 750),
            array('force', null, InputOption::VALUE_NONE, 'Overwrite existing indices and documents', null)
        );

        /**
         *
         * Assertion
         *
         **/
        $this->assertEquals($options, $command->getOptions());
    }

    /**
     * @test
     */
    public function it_should_get_arguments()
    {
        /**
         *
         * Set
         *
         **/
        $command = m::mock('Iverberk\Larasearch\Commands\ReindexCommand');
        $arguments = array(
            array('model', InputOption::VALUE_OPTIONAL, 'Eloquent model to reindex', null)
        );

        /**
         *
         * Assertion
         *
         **/
        $this->assertEquals($arguments, $command->getArguments());
    }

    /**
     * @test
     */
    public function it_should_fire_without_models()
    {
        /**
         *
         * Set
         *
         **/
        /* @var \Mockery\Mock $command */
        $command = m::mock('Iverberk\Larasearch\Commands\ReindexCommand')->makePartial();

        /**
         *
         * Expectation
         *
         */
        $command->shouldReceive('argument')
            ->with('model')
            ->once()
            ->andReturn([]);

        $command->shouldReceive('option')
            ->with('dir')
            ->once()
            ->andReturn([]);

        $command->shouldReceive('info')
            ->once()
            ->andReturn(true);

        /**
         *
         * Assertion
         *
         */
        $command->fire();
    }

    /**
     * @test
     */
    public function it_should_fire_with_models()
    {
        /**
         *
         * Set
         *
         */
        /* @var \Mockery\Mock $command */
        $command = m::mock('Iverberk\Larasearch\Commands\ReindexCommand')->makePartial();
        $command->shouldAllowMockingProtectedMethods();

        $model = m::mock('Husband');

        /**
         *
         * Expectation
         *
         */
        $model->shouldReceive('reindex')
            ->with(true, true, 750, null, \Mockery::type('closure'))
            ->times(4)
            ->andReturnUsing(function($force, $relations, $batch, $mapping, $callback) {
                $callback(1);
            });

        $command->shouldReceive('argument')
            ->with('model')
            ->once()
            ->andReturn(['Husband']);

        $command->shouldReceive('option')
            ->with('dir')
            ->once()
            ->andReturn([__DIR__ . '/../../../Support/Stubs']);

        $command->shouldReceive('option')
            ->with('mapping')
            ->times(4)
            ->andReturn(false);

        $command->shouldReceive('option')
            ->with('force')
            ->times(4)
            ->andReturn(true);

        $command->shouldReceive('option')
            ->with('relations')
            ->times(4)
            ->andReturn(true);

        $command->shouldReceive('option')
            ->with('batch')
            ->times(4)
            ->andReturn(750);

        $command->shouldReceive('info')->andReturn(true);

        $command->shouldReceive('getModelInstance')->times(4)->andReturn($model);

        /**
         *
         * Assertion
         *
         */
        $command->fire();
    }

    /**
     * @test
     */
    public function it_should_get_a_model_instance()
    {
        /**
         *
         * Set
         *
         */
        $command = m::mock('Iverberk\Larasearch\Commands\ReindexCommand')->makePartial();
        $command->shouldAllowMockingProtectedMethods();

        /**
         *
         * Assertion
         *
         */
        $model = $command->getModelInstance('Husband');

        assertInstanceOf('Husband', $model);
    }

} 