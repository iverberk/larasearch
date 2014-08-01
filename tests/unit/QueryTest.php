<?php


use Iverberk\Larasearch\Proxy;

class QueryTest extends \Codeception\TestCase\Test
{
   /**
    * @var \UnitTester
    */
    protected $tester;

    protected $query;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testThatQueryCanBeConstructed()
    {
        $query = new \Iverberk\Larasearch\Query(new Proxy(new Husband()), '', []);

        $this->assertInstanceOf('Iverberk\Larasearch\Query', $query);
    }

    public function testThatQueryCanBeExecuted()
    {
        $query = new \Iverberk\Larasearch\Query(new Proxy(new Husband()), '', []);

        $response = $query->execute();

        $this->assertInstanceOf('Iverberk\Larasearch\Response', $response);
        $this->assertInstanceOf('Husband', $response->getModel());
        $this->assertEquals(0, $response->getResponse()['hits']['total']);
    }

}