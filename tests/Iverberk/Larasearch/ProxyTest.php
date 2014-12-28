<?php namespace Iverberk\Larasearch;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Facade;
use Mockery as m;
use AspectMock\Test as am;

function date()
{
    return ProxyTest::$functions->date();
}

class ProxyTest extends \PHPUnit_Framework_TestCase {

    /**
     * @var \Mockery\Mock
     */
    private $proxy;

    /**
     * @var \Mockery\Mock
     */
    private $model;

    /**
     * @var \Mockery\Mock
     */
    private $index;

    /**
     * @var \Mockery\Mock
     */
    private $client;

    /**
     * @var \Mockery\Mock
     */
    public static $functions;

    protected function setUp()
    {
        parent::setUp();

        self::$functions = m::mock();

        $this->index = m::mock('Iverberk\Larasearch\Index');
        $this->model = m::mock('Husband')->makePartial();
        $this->client = m::mock('Elasticsearch\Client');

        $this->model->shouldReceive('getTable')
            ->once()
            ->andReturn('Husbands');

        Facade::clearResolvedInstances();
        App::shouldReceive('make')
            ->with('Elasticsearch')
            ->once()
            ->andReturn($this->client);

        App::shouldReceive('make')
            ->with('iverberk.larasearch.index', m::type('array'))
            ->once()
            ->andReturn($this->index);

        $this->proxy = new Proxy($this->model);

        self::$functions->shouldReceive('date')->andReturn('9999');
    }

    protected function tearDown()
    {
        m::close();
        am::clean();
    }

    /**
     * @test
     */
    public function it_can_get_config()
    {
        $this->assertEquals([
            'model' => $this->model,
            'type' => 'Husband',
            'client' => $this->client,
            'index' => $this->index,
            'autocomplete' => ['name', 'wife.name'],
            'suggest' => ['name'],
            'text_start' => ['name', 'wife.children.name'],
            'text_middle' => ['name', 'wife.children.name'],
            'text_end' => ['name', 'wife.children.name'],
            'word_start' => ['name', 'wife.children.name'],
            'word_middle' => ['name', 'wife.children.name'],
            'word_end' => ['name', 'wife.children.name']
        ],
        $this->proxy->getConfig());
    }

    /**
     * @test
     */
    public function it_can_get_model()
    {
        $this->assertEquals($this->model, $this->proxy->getModel());
    }

    /**
     * @test
     */
    public function it_can_get_index()
    {
        $this->assertEquals($this->index, $this->proxy->getIndex());
    }

    /**
     * @test
     */
    public function it_can_get_type()
    {
        $this->assertEquals('Husband', $this->proxy->getType());
    }

    /**
     * @test
     */
    public function it_can_get_client()
    {
        $this->assertEquals($this->client, $this->proxy->getClient());
    }

    /**
     * @test
     */
    public function it_can_search()
    {
        /**
         *
         * Set
         *
         */
        $query = m::mock('Iverberk\Larasearch\Query');

        /**
         *
         * Expectation
         *
         */
        $query->shouldReceive('execute')->andReturn('result');

        App::shouldReceive('make')
            ->with('iverberk.larasearch.query', [ 'proxy' => $this->proxy, 'term' => '*', 'options' => ['option']])
            ->once()
            ->andReturn($query);

        /**
         *
         * Assertion
         *
         */
        $result = $this->proxy->search('*', ['option']);

        $this->assertEquals('result', $result);
    }

	/**
	 * @test
	 */
	public function it_can_search_with_a_query()
	{
		/**
		 *
		 * Set
		 *
		 */
		$queryMock = m::mock('Iverberk\Larasearch\Query');

		$query['index'] = 'my_index';
		$query['type']  = 'my_type';
		$query['body']['query']['match']['testField'] = 'abc';

		/**
		 *
		 * Expectation
		 *
		 */
		$queryMock->shouldReceive('execute')->andReturn('result');

		App::shouldReceive('make')
			->with('iverberk.larasearch.query', [
				'proxy' => $this->proxy,
				'term' => null,
				'options' => array_merge(['query' => $query], ['option'])])
			->once()
			->andReturn($queryMock);

		/**
		 *
		 * Assertion
		 *
		 */
		$result = $this->proxy->searchQuery($query, ['option']);

		$this->assertEquals('result', $result);
	}

	/**
	 * @test
	 */
	public function it_can_search_for_a_single_document()
	{
		/**
		 *
		 * Set
		 *
		 */
		$query['index'] = 'my_index';
		$query['type']  = 'my_type';
		$query['id'] = 'abc';

		/**
		 *
		 * Expectation
		 *
		 */
		$this->index->shouldReceive('getName')->andReturn('index');
		$this->client->shouldReceive('get')->andReturn(['hit']);

		App::shouldReceive('make')
			->with('iverberk.larasearch.response.result', ['hit'])
			->once()
			->andReturn('result');

		/**
		 *
		 * Assertion
		 *
		 */
		$result = $this->proxy->searchOne('abc');

		$this->assertEquals('result', $result);
	}

    /**
     * @test
     */
    public function it_can_reindex_when_alias_does_not_exist()
    {
        /**
         *
         * Set
         *
         */
        $indexDouble = am::double('Iverberk\Larasearch\Index', ['refresh' => null, 'clean' => null, 'updateAliases' => null]);
        $indexMock = m::mock('Iverberk\Larasearch\Index');

        /**
         *
         * Expectation
         *
         */
        $this->index->shouldReceive('getName')->andReturn('Husband');
        $this->index->shouldReceive('exists')->once()->andReturn(true);
        $this->index->shouldReceive('delete')->once()->andReturn();

        App::shouldReceive('make')
            ->with('iverberk.larasearch.index', ['name' => 'Husband_9999', 'proxy' => $this->proxy])
            ->andReturn($indexMock);

        $indexMock->shouldReceive('create')->once()->andReturn();
        $indexMock->shouldReceive('aliasExists')->once()->andReturn(false);
        $indexMock->shouldReceive('import')->andReturn();

        /**
         *
         * Assertion
         *
         */
        $this->proxy->reindex();

        $indexDouble->verifyInvoked('refresh', 'Husband');
        $indexDouble->verifyInvoked('clean', 'Husband');
    }

    /**
     * @test
     */
    public function it_can_reindex_when_alias_exists()
    {
        /**
         *
         * Set
         *
         */
        $indexDouble = am::double('Iverberk\Larasearch\Index', [
            'refresh' => null,
            'clean' => null,
            'updateAliases' => null,
            'getAlias' => ['mockIndex' => 'aliases']
        ]);
        $indexMock = m::mock('Iverberk\Larasearch\Index');

        $operations[] = [
            'add' => [
                'alias' => 'Husband',
                'index' => 'Husband_9999'
            ],
            'remove' => [
                'alias' => 'Husband',
                'index' => 'mockIndex'
            ]
        ];

        $actions[] = ['actions' => $operations];
        $test = $this;

        /**
         *
         * Expectation
         *
         */
        $this->index->shouldReceive('getName')->andReturn('Husband');

        $indexMock->shouldReceive('create')->once()->andReturn();
        $indexMock->shouldReceive('aliasExists')->once()->andReturn(true);
        $indexMock->shouldReceive('import')->andReturn();

        App::shouldReceive('make')
            ->with('iverberk.larasearch.index', ['name' => 'Husband_9999', 'proxy' => $this->proxy])
            ->andReturn($indexMock);

        /**
         *
         * Assertion
         *
         */
        $this->proxy->reindex();

        $indexDouble->verifyInvoked('refresh', 'Husband');
        $indexDouble->verifyInvoked('clean', 'Husband');
        $indexDouble->verifyInvoked('updateAliases', function($calls) use ($test, $actions) {
            $test->assertEquals($actions, $calls[0]);
        });
    }

	/**
	 * @test
	 */
	public function it_should_get_elasticsearch_id()
	{
		$this->model->shouldReceive('getAttribute')->with('id')->andReturn(1);

		$this->assertEquals(1, $this->proxy->getEsId());
	}

    /**
     * @test
     */
    public function it_should_index()
    {
        $this->assertEquals(true, $this->proxy->shouldIndex());
    }

    /**
     * @test
     */
    public function it_should_refresh_docs()
    {
        /**
         *
         * Expectation
         *
         */
        $this->model->shouldReceive('transform')
            ->with(true)
            ->andReturn('body');

	    $this->model->shouldReceive('getEsId')
		    ->andReturn(1);

        $this->client->shouldReceive('index')
            ->with([
                'id' => '1',
                'index' => 'Husband',
                'type' => 'Husband',
                'body' => 'body'
            ])
            ->andReturn();

        $this->index->shouldReceive('getName')
            ->andReturn('Husband');

        /**
         *
         * Assertion
         *
         */
        $this->proxy->refreshDoc($this->model);
    }

	/**
	 * @test
	 */
	public function it_should_delete_docs()
	{
		/**
		 *
		 * Expectation
		 *
		 */
		$this->client->shouldReceive('delete')
			->with([
				'id' => 1,
				'index' => 'Husband',
				'type' => 'Husband'
			])
			->andReturn();

		$this->index->shouldReceive('getName')
			->andReturn('Husband');

		/**
		 *
		 * Assertion
		 *
		 */
		$this->proxy->deleteDoc(1);
	}

}