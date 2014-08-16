<?php namespace Iverberk\Larasearch;

use Mockery as m;

class ResponseTest extends \PHPUnit_Framework_TestCase {

    private $responseFixture = [
        'took' => 'took',
        'timed_out' => 'timed_out',
        '_shards' => 'shards',
        'suggest' => [
            'key_dummy' => 'value_dummy'
        ],
        'aggregations' => [
            'named_aggregation' => 'named_aggregation'
        ],
        'hits' => [
            'total' => 'total',
            'max_score' => 'max_score',
            'hits' => [
                ['_id' => '1']
            ]
        ]

    ];

    protected function tearDown()
    {
        m::close();
    }

    /**
     * @test
     */
    public function it_should_get_model()
    {
        /**
         *
         * Set
         *
         */
        list($response, $model) = $this->getMocks();

        /**
         *
         * Assertion
         *
         */
        $this->assertEquals($model, $response->getModel());
    }

    /**
     * @test
     */
    public function it_should_get_response()
    {
        /**
         *
         * Set
         *
         */
        list($response, $model) = $this->getMocks();

        /**
         *
         * Assertion
         *
         */
        $this->assertEquals(
            $this->responseFixture,
            $response->getResponse());
    }

    /**
     * @test
     */
    public function it_should_get_results()
    {
        /**
         *
         * Set
         *
         */
        list($response, $model) = $this->getMocks();

        /**
         *
         * Assertion
         *
         */
        $results = $response->getResults();

        $this->assertInstanceOf('Iverberk\Larasearch\Response\Results', $results);
        $this->assertEquals(1, $results->first()->getId());
    }

    /**
     * @test
     */
    public function it_should_get_records()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $model
         */
        list($response, $model) = $this->getMocks();

        /**
         *
         * Expectation
         *
         */
        $model->shouldReceive('get')->andReturn('succes');
        $model->shouldReceive('whereIn')
            ->with('id',[1])
            ->andReturn($model);

        /**
         *
         * Assertion
         *
         */
        $records = $response->getRecords();

        $this->assertEquals('succes', $records);
    }

    /**
     * @test
     */
    public function it_should_get_took()
    {
        /**
         *
         * Set
         *
         */
        list($response, $model) = $this->getMocks();

        /**
         *
         * Assertion
         *
         */
        $this->assertEquals('took', $response->getTook());
    }

    /**
     * @test
     */
    public function it_should_get_hits()
    {
        /**
         *
         * Set
         *
         */
        list($response, $model) = $this->getMocks();

        /**
         *
         * Assertion
         *
         */
        $this->assertEquals([['_id' => 1]], $response->getHits());
    }

    /**
     * @test
     */
    public function it_should_get_timed_out()
    {
        /**
         *
         * Set
         *
         */
        list($response, $model) = $this->getMocks();

        /**
         *
         * Assertion
         *
         */
        $this->assertEquals('timed_out', $response->getTimedOut());
    }

    /**
     * @test
     */
    public function it_should_get_shards()
    {
        /**
         *
         * Set
         *
         */
        list($response, $model) = $this->getMocks();

        /**
         *
         * Assertion
         *
         */
        $this->assertEquals('shards', $response->getShards());
    }

    /**
     * @test
     */
    public function it_should_get_max_score()
    {
        /**
         *
         * Set
         *
         */
        list($response, $model) = $this->getMocks();

        /**
         *
         * Assertion
         *
         */
        $this->assertEquals('max_score', $response->getMaxScore());
    }

    /**
     * @test
     */
    public function it_should_get_total()
    {
        /**
         *
         * Set
         *
         */
        list($response, $model) = $this->getMocks();

        /**
         *
         * Assertion
         *
         */
        $this->assertEquals('total', $response->getTotal());
    }

    /**
     * @test
     */
    public function it_should_get_suggestions_with_fields()
    {
        /**
         *
         * Set
         *
         */
        list($response, $model) = $this->getMocks();

        /**
         *
         * Assertion
         *
         */
        $suggestions = $response->getSuggestions(['key']);

        $this->assertEquals(['key' => 'value_dummy'], $suggestions);
    }

    /**
     * @test
     */
    public function it_should_get_suggestions_without_fields()
    {
        /**
         *
         * Set
         *
         */
        list($response, $model) = $this->getMocks();

        /**
         *
         * Assertion
         *
         */
        $suggestions = $response->getSuggestions();

        $this->assertEquals(['key_dummy' => 'value_dummy'], $suggestions);
    }

    /**
     * @test
     */
    public function it_should_get_aggregations_with_name()
    {
        /**
         *
         * Set
         *
         */
        list($response, $model) = $this->getMocks();

        /**
         *
         * Assertion
         *
         */
        $aggregations = $response->getAggregations('named_aggregation');

        $this->assertEquals('named_aggregation', $aggregations);
    }

    /**
     * @test
     */
    public function it_should_get_aggregations_without_name()
    {
        /**
         *
         * Set
         *
         */
        list($response, $model) = $this->getMocks();

        /**
         *
         * Assertion
         *
         */
        $aggregations = $response->getAggregations();

        $this->assertEquals(['named_aggregation' => 'named_aggregation'], $aggregations);
    }


    /**
     * Construct a Response mock
     *
     * @return array
     */
    private function getMocks()
    {
        $model = m::mock('Husband');

        $response = m::mock('Iverberk\Larasearch\Response', array($model, $this->responseFixture))->makePartial();

        return [$response, $model];
    }


} 