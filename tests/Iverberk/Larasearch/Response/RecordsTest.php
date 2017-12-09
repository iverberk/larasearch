<?php namespace Iverberk\Larasearch\Response;

use Mockery as m;

class RecordsTest extends \PHPUnit_Framework_TestCase {

    protected function tearDown()
    {
        m::close();
    }

    /**
     * @test
     */
    public function it_should_construct()
    {
        /**
         *
         * Set
         *
         */
        $husbandMock = m::mock('Husband');
        $response = m::mock('Iverberk\Larasearch\Response');
        $test = $this;

        /**
         *
         * Expectation
         *
         */
        $husbandMock->shouldReceive('whereIn')
            ->andReturnUsing(function ($attribute, $items) use ($test, $husbandMock)
            {
                $test->assertEquals('id', $attribute);
                $test->assertEquals([1, 2], $items);
                return $husbandMock;
            });
        $husbandMock->shouldReceive('get->toArray')
            ->andReturn(['item1', 'item2']);

        $response->shouldReceive('getHits')->andReturn([['_id' => 1], ['_id' => 2]]);
        $response->shouldReceive('getModel')->andReturn($husbandMock);

        /**
         *
         * Assertion
         *
         */
        $records = new Records($response);

        $this->assertInstanceOf('Illuminate\Support\Collection', $records);
        $this->assertEquals('item1', $records->first());
        $this->assertEquals('item2', $records[1]);
    }

}
