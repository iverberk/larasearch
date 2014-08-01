<?php

use Iverberk\Larasearch\Proxy;

class ProxyTest extends \Codeception\TestCase\Test
{
   /**
    * @var \UnitTester
    */
    protected $tester;

    /**
     * @var Proxy
     */
    protected $proxy;

    protected function _before()
    {
        $this->proxy = new \Iverberk\Larasearch\Proxy(new Husband);
    }

    protected function _after()
    {
    }

    public function testThatAProxyCanBeConstructed()
    {
        $this->assertInstanceOf('Iverberk\Larasearch\proxy', $this->proxy);
    }

    public function testThatConfigurationCanRetrieved()
    {
        $config = $this->proxy->getConfig();

        $this->assertArrayHasKey('model', $config);
        $this->assertArrayHasKey('index', $config);
        $this->assertArrayHasKey('client', $config);
        $this->assertArrayHasKey('type', $config);

        $model = $this->proxy->getModel();
        $this->assertInstanceOf('Illuminate\Database\Eloquent\Model', $model);

        $index = $this->proxy->getIndex();
        $this->assertInstanceOf('Iverberk\Larasearch\Index', $index);

        $type = $this->proxy->getType();
        $this->assertEquals('husband', $type);

        $client = $this->proxy->getClient();
        $this->assertInstanceOf('Elasticsearch\Client', $client);
    }

    public function testReindexing()
    {
        list($errors, $items) = $this->proxy->reindex(true, true);

        $this->assertEquals(false, $errors);
        $this->assertEquals([], $items);

        // Give Elasticsearch some time to process
        sleep(1);
    }

    public function testThatModelShouldIndex()
    {
        $result = $this->proxy->shouldIndex();

        $this->assertEquals(true, $result);
    }

    public function testBasicSearching()
    {
        $response = $this->proxy->search('*');

        $this->assertInstanceOf('Iverberk\Larasearch\Response', $response);
        $this->assertEquals(10, $response->getResponse()['hits']['total']);
    }

    public function testHighlightedSearching()
    {
        $results = $this->proxy->search('Raymundo', ['fields' => ['name'], 'highlight' => true])->getResults();

        $this->assertInstanceOf('Iverberk\Larasearch\Response\Results', $results);
        $this->assertEquals(1, $results->count());
        $this->assertArrayHasKey('name.analyzed', $results->first()->getHighlights());
        $this->assertEquals(['name' => ['<em>Raymundo</em>']], $results->first()->getHighlights(['name']));
    }

}