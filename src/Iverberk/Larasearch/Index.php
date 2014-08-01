<?php namespace Iverberk\Larasearch;

use Illuminate\Support\Facades\App;

class Index {

    /**
     * Index name
     *
     * @var string
     */
    private $name;

    /**
     * Elasticsearch client
     *
     * @var \Elasticsearch\Client
     */
    private $client;

    /**
     * Create an index instance
     */
    public function __construct($name)
    {
        $this->client = App::make('Elasticsearch');
        $this->name = $name;
    }

    /**
     * Set index name
     *
     * @param string
     * @return Index
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get index name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Create a new index
     *
     * @param array $options
     */
    public function create($options = [])
    {
        $this->client->indices()->create(['index' => $this->name, 'body' => $options]);
    }

    /**
     * Delete an index
     */
    public function delete()
    {
        $this->client->indices()->delete(['index' => $this->name]);
    }

    /**
     * Check if an index exists
     *
     * @return bool
     */
    public function exists()
    {
        return $this->client->indices()->exists(['index' => $this->name]);
    }

    /**
     * Refresh an index
     */
    public function refresh()
    {
        $this->client->indices()->refresh(['index' => $this->name]);
    }

    /**
     * Store a record in the index
     *
     * @param $record
     */
    public function store($record)
    {
        $params['index'] = $this->name;
        $params['type'] = $record['type'];
        $params['id'] = $record['id'];
        $params['body'] = $record['data'];

        $this->client->index($params);
    }

    /**
     * Retrieve a record from the index
     *
     * @param $record
     */
    public function retrieve($record)
    {
        $params['index'] = $this->name;
        $params['type'] = $record['type'];
        $params['id'] = $record['id'];

        $this->client->get($params);
    }

    /**
     * Remove a record from the index
     *
     * @param $record
     */
    public function remove($record)
    {
        $params['index'] = $this->name;
        $params['type'] = $record['type'];
        $params['id'] = $record['id'];

        $this->client->delete($params);
    }

    /**
     * Bulk import a set of data into the index
     *
     * @param $type
     * @param $records
     */
    public function import($type, $records)
    {
        $params['index'] = $this->name;
        $params['type'] = $type;
        $params['body'] = $records;

        $this->client->bulk($params);
    }

    /**
     * Inspect tokens returned from the analyzer
     *
     * @param string $text
     * @param array $options
     */
    public function tokens($text, $options = [])
    {
        $this->client->indices()->analyze(array_merge(['index' => $this->name, 'text' => $text], $options));
    }

}