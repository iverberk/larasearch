<?php namespace Iverberk\Larasearch\Response;

use Mockery as m;

class ResultsTest extends \PHPUnit_Framework_TestCase {

    protected function tearDown()
    {
        m::close();
    }

    /**
     * @test
     */
    public function it_should_construct()
    {
        $hit = [
            '_id' => 1,
            '_type' => 2,
            '_index' => 3,
            '_score' => 4,
            '_source' => [
                'id' => 5,
                'foo' => 'bar'
            ],
            'fields' => [
                'field1' => 'value1',
                'field2' => 'value2',
                'nested' => [
                    'nested_field' => 'nested_value'
                ]
            ],
            'highlight' => [
                'field3' => 'value3',
                'field4' => 'value4'
            ]
        ];

        /**
         *
         * Set
         *
         */
        $response = m::mock('Iverberk\Larasearch\Response');

        /**
         *
         * Expectation
         *
         */
        $response->shouldReceive('getHits')->andReturn([$hit, $hit]);

        /**
         *
         * Assertion
         *
         */
        $results = new Results($response);

        $this->assertInstanceOf('Illuminate\Support\Collection', $results);
        $this->assertInstanceOf('Iverberk\Larasearch\Response\Result', $results->first());
    }
} 