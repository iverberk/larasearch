<?php namespace Iverberk\Larasearch\Response;

use Mockery as m;

class ResultTest extends \PHPUnit_Framework_Testcase {

    /**
     * @var \Mockery\Mock
     */
    private $result;

    /**
     * @var array
     */
    private $hit;

    protected function setUp()
    {
        $this->hit = [
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

        $result = new Result($this->hit);

        $this->result = m::mock($result);
    }

    protected function tearDown()
    {
        m::close();
    }

    /**
     * @test
     */
    public function it_should_get_id()
    {
        $this->assertEquals(1, $this->result->getId());
    }

    /**
     * @test
     */
    public function it_should_get_type()
    {
        $this->assertEquals(2, $this->result->getType());
    }

    /**
     * @test
     */
    public function it_should_get_index()
    {
        $this->assertEquals(3, $this->result->getIndex());
    }

    /**
     * @test
     */
    public function it_should_get_score()
    {
        $this->assertEquals(4, $this->result->getScore());
    }

    /**
     * @test
     */
    public function it_should_get_source()
    {
        $this->assertEquals(['id' => 5, 'foo' => 'bar'], $this->result->getSource());
    }

    /**
     * @test
     */
    public function it_should_get_fields()
    {
        $this->assertEquals(['field1' => 'value1', 'field2' => 'value2', 'nested' => ['nested_field' => 'nested_value']], $this->result->getFields());
        $this->assertEquals(['field1' => 'value1'], $this->result->getFields(['field1']));
        $this->assertEquals(['field1' => 'value1', 'field2' => 'value2'], $this->result->getFields(['field1', 'field2']));
    }

    /**
     * @test
     */
    public function it_should_get_hit()
    {
        $this->assertEquals($this->hit, $this->result->getHit());
    }

    /**
     * @test
     */
    public function it_should_get_highlights()
    {
        $this->assertEquals($this->hit['highlight'], $this->result->getHighLights());
        $this->assertEquals(['field3' => 'value3'], $this->result->getHighLights(['field3']));
        $this->assertEquals(['field3' => 'value3', 'field4' => 'value4'], $this->result->getHighLights(['field3', 'field4']));
    }

    /**
     * @test
     */
    public function it_should_get_attributes_from_hit()
    {
        $result = new Result($this->hit);

        $this->assertEquals('bar', $result->foo);
        $this->assertEquals('nested_value', $result['fields.nested.nested_field']);
    }

    /**
     * @test
     */
    public function it_should_convert_to_array()
    {
        $this->assertEquals([
            'id' => 5,
            'foo' => 'bar'
        ],
        $this->result->toArray());
    }

    /**
     * The set and unset function will never be implemented
     * but who doesn't like 100% test coverage ;-)
     *
     * @test
     */
    public function it_should_cover_hundred_procent()
    {
        $this->result['foo'] = 'bar';
        unset($this->result['foo']);
    }

} 