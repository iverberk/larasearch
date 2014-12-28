<?php namespace Iverberk\Larasearch;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Facade;
use Mockery as m;
use AspectMock\Test as am;


class IndexTest extends \PHPUnit_Framework_TestCase {

    protected function tearDown()
    {
        m::close();
        am::clean();
    }

    /**
     * @test
     */
    public function it_should_import()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         */
        list($index) = $this->getMocks();
        $test = $this;

        /**
         *
         * Expectation
         *
         */

        /* @var \Mockery\Mock $model */
        $model = m::mock('Illuminate\Database\Eloquent\Model')->makePartial();

        $model->shouldReceive('with->skip->take->get')
            ->twice()
            ->andReturn([
                $model, $model, $model
            ], []);

	    $model->shouldReceive('getEsId')
		    ->times(3)
		    ->andReturn(1);

        $model->shouldReceive('transform')
            ->times(3)
            ->andReturn(['mock', 'data']);

        $index->shouldReceive('bulk')
            ->andReturn();

        /**
         *
         * Assertion
         *
         */
        $index->import($model, [], 750, function($batch) use ($test) {
            $test->assertEquals(1, $batch);
        });
    }

    /**
     * @test
     */
    public function it_should_set_name()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         */
        list($index) = $this->getMocks('bar_');

        /**
         *
         * Assertion
         *
         */
        $this->assertEquals($index, $index->setName('Mock'));
        $this->assertEquals('bar_mock', $index->getName());
    }

    /**
     * @test
     */
    public function it_should_get_name()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         */
        list($index) = $this->getMocks();

        /**
         *
         * Assertion
         *
         */
        $this->assertEquals('husband', $index->getName());
    }

    /**
     * @test
     */
    public function it_should_create_an_index()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();
        $test = $this;

        /**
         *
         * Expectation
         *
         */
        Config::shouldReceive('get')
            ->with('larasearch::elasticsearch.analyzers')
            ->andReturn([
                'autocomplete',
                'suggest',
                'text_start',
                'text_middle',
                'text_end',
                'word_start',
                'word_middle',
                'word_end'
            ]);

        Config::shouldReceive('get')
            ->with('larasearch::elasticsearch.defaults.index')
            ->andReturn([
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'analysis' => [
                        'analyzer' => [
                            'larasearch_keyword' => [
                                'type' => "custom",
                                'tokenizer' => "keyword",
                                'filter' => ["lowercase", "larasearch_stemmer"]
                            ],
                            'default_index' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["standard", "lowercase", "asciifolding", "larasearch_index_shingle", "larasearch_stemmer"]
                            ],
                            'larasearch_search' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["standard", "lowercase", "asciifolding", "larasearch_search_shingle", "larasearch_stemmer"]
                            ],
                            'larasearch_search2' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["standard", "lowercase", "asciifolding", "larasearch_stemmer"]
                            ],
                            'larasearch_autocomplete_index' => [
                                'type' => "custom",
                                'tokenizer' => "larasearch_autocomplete_ngram",
                                'filter' => ["lowercase", "asciifolding"]
                            ],
                            'larasearch_autocomplete_search' => [
                                'type' => "custom",
                                'tokenizer' => "keyword",
                                'filter' => ["lowercase", "asciifolding"]
                            ],
                            'larasearch_word_search' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["lowercase", "asciifolding"]
                            ],
                            'larasearch_suggest_index' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["lowercase", "asciifolding", "larasearch_suggest_shingle"]
                            ],
                            'larasearch_text_start_index' => [
                                'type' => "custom",
                                'tokenizer' => "keyword",
                                'filter' => ["lowercase", "asciifolding", "larasearch_edge_ngram"]
                            ],
                            'larasearch_text_middle_index' => [
                                'type' => "custom",
                                'tokenizer' => "keyword",
                                'filter' => ["lowercase", "asciifolding", "larasearch_ngram"]
                            ],
                            'larasearch_text_end_index' => [
                                'type' => "custom",
                                'tokenizer' => "keyword",
                                'filter' => ["lowercase", "asciifolding", "reverse", "larasearch_edge_ngram", "reverse"]
                            ],
                            'larasearch_word_start_index' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["lowercase", "asciifolding", "larasearch_edge_ngram"]
                            ],
                            'larasearch_word_middle_index' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["lowercase", "asciifolding", "larasearch_ngram"]
                            ],
                            'larasearch_word_end_index' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["lowercase", "asciifolding", "reverse", "larasearch_edge_ngram", "reverse"]
                            ]
                        ],
                        'filter' => [
                            'larasearch_index_shingle' => [
                                'type' => "shingle",
                                'token_separator' => ""
                            ],
                            'larasearch_search_shingle' => [
                                'type' => "shingle",
                                'token_separator' => "",
                                'output_unigrams' => false,
                                'output_unigrams_if_no_shingles' => true
                            ],
                            'larasearch_suggest_shingle' => [
                                'type' => "shingle",
                                'max_shingle_size' => 5
                            ],
                            'larasearch_edge_ngram' => [
                                'type' => "edgeNGram",
                                'min_gram' => 1,
                                'max_gram' => 50
                            ],
                            'larasearch_ngram' => [
                                'type' => "nGram",
                                'min_gram' => 1,
                                'max_gram' => 50
                            ],
                            'larasearch_stemmer' => [
                                'type' => "snowball",
                                'language' => "English"
                            ]
                        ],
                        'tokenizer' => [
                            'larasearch_autocomplete_ngram' => [
                                'type' => "edgeNGram",
                                'min_gram' => 1,
                                'max_gram' => 50
                            ]
                        ]
                    ]
                ]]);

        $client->shouldReceive('indices->create')
            ->andReturnUsing(function($params) use ($test) {
                $test->assertEquals(json_decode(
                        '{
                          "index": "husband",
                          "body": {
                            "settings": {
                              "number_of_shards": 1,
                              "number_of_replicas": 0,
                              "analysis": {
                                "analyzer": {
                                  "larasearch_keyword": {
                                    "type": "custom",
                                    "tokenizer": "keyword",
                                    "filter": [
                                      "lowercase",
                                      "larasearch_stemmer"
                                    ]
                                  },
                                  "default_index": {
                                    "type": "custom",
                                    "tokenizer": "standard",
                                    "filter": [
                                      "standard",
                                      "lowercase",
                                      "asciifolding",
                                      "larasearch_index_shingle",
                                      "larasearch_stemmer"
                                    ]
                                  },
                                  "larasearch_search": {
                                    "type": "custom",
                                    "tokenizer": "standard",
                                    "filter": [
                                      "standard",
                                      "lowercase",
                                      "asciifolding",
                                      "larasearch_search_shingle",
                                      "larasearch_stemmer"
                                    ]
                                  },
                                  "larasearch_search2": {
                                    "type": "custom",
                                    "tokenizer": "standard",
                                    "filter": [
                                      "standard",
                                      "lowercase",
                                      "asciifolding",
                                      "larasearch_stemmer"
                                    ]
                                  },
                                  "larasearch_autocomplete_index": {
                                    "type": "custom",
                                    "tokenizer": "larasearch_autocomplete_ngram",
                                    "filter": [
                                      "lowercase",
                                      "asciifolding"
                                    ]
                                  },
                                  "larasearch_autocomplete_search": {
                                    "type": "custom",
                                    "tokenizer": "keyword",
                                    "filter": [
                                      "lowercase",
                                      "asciifolding"
                                    ]
                                  },
                                  "larasearch_word_search": {
                                    "type": "custom",
                                    "tokenizer": "standard",
                                    "filter": [
                                      "lowercase",
                                      "asciifolding"
                                    ]
                                  },
                                  "larasearch_suggest_index": {
                                    "type": "custom",
                                    "tokenizer": "standard",
                                    "filter": [
                                      "lowercase",
                                      "asciifolding",
                                      "larasearch_suggest_shingle"
                                    ]
                                  },
                                  "larasearch_text_start_index": {
                                    "type": "custom",
                                    "tokenizer": "keyword",
                                    "filter": [
                                      "lowercase",
                                      "asciifolding",
                                      "larasearch_edge_ngram"
                                    ]
                                  },
                                  "larasearch_text_middle_index": {
                                    "type": "custom",
                                    "tokenizer": "keyword",
                                    "filter": [
                                      "lowercase",
                                      "asciifolding",
                                      "larasearch_ngram"
                                    ]
                                  },
                                  "larasearch_text_end_index": {
                                    "type": "custom",
                                    "tokenizer": "keyword",
                                    "filter": [
                                      "lowercase",
                                      "asciifolding",
                                      "reverse",
                                      "larasearch_edge_ngram",
                                      "reverse"
                                    ]
                                  },
                                  "larasearch_word_start_index": {
                                    "type": "custom",
                                    "tokenizer": "standard",
                                    "filter": [
                                      "lowercase",
                                      "asciifolding",
                                      "larasearch_edge_ngram"
                                    ]
                                  },
                                  "larasearch_word_middle_index": {
                                    "type": "custom",
                                    "tokenizer": "standard",
                                    "filter": [
                                      "lowercase",
                                      "asciifolding",
                                      "larasearch_ngram"
                                    ]
                                  },
                                  "larasearch_word_end_index": {
                                    "type": "custom",
                                    "tokenizer": "standard",
                                    "filter": [
                                      "lowercase",
                                      "asciifolding",
                                      "reverse",
                                      "larasearch_edge_ngram",
                                      "reverse"
                                    ]
                                  }
                                },
                                "filter": {
                                  "larasearch_index_shingle": {
                                    "type": "shingle",
                                    "token_separator": ""
                                  },
                                  "larasearch_search_shingle": {
                                    "type": "shingle",
                                    "token_separator": "",
                                    "output_unigrams": false,
                                    "output_unigrams_if_no_shingles": true
                                  },
                                  "larasearch_suggest_shingle": {
                                    "type": "shingle",
                                    "max_shingle_size": 5
                                  },
                                  "larasearch_edge_ngram": {
                                    "type": "edgeNGram",
                                    "min_gram": 1,
                                    "max_gram": 50
                                  },
                                  "larasearch_ngram": {
                                    "type": "nGram",
                                    "min_gram": 1,
                                    "max_gram": 50
                                  },
                                  "larasearch_stemmer": {
                                    "type": "snowball",
                                    "language": "English"
                                  }
                                },
                                "tokenizer": {
                                  "larasearch_autocomplete_ngram": {
                                    "type": "edgeNGram",
                                    "min_gram": 1,
                                    "max_gram": 50
                                  }
                                }
                              }
                            },
                            "mappings": {
                              "_default_": {
                                "properties": {
                                  "name": {
                                    "type": "multi_field",
                                    "fields": {
                                      "name": {
                                        "type": "string",
                                        "index": "not_analyzed"
                                      },
                                      "analyzed": {
                                        "type": "string",
                                        "index": "analyzed"
                                      },
                                      "autocomplete": {
                                        "type": "string",
                                        "index": "analyzed",
                                        "analyzer": "larasearch_autocomplete_index"
                                      },
                                      "suggest": {
                                        "type": "string",
                                        "index": "analyzed",
                                        "analyzer": "larasearch_suggest_index"
                                      },
                                      "text_start": {
                                        "type": "string",
                                        "index": "analyzed",
                                        "analyzer": "larasearch_text_start_index"
                                      },
                                      "text_middle": {
                                        "type": "string",
                                        "index": "analyzed",
                                        "analyzer": "larasearch_text_middle_index"
                                      },
                                      "text_end": {
                                        "type": "string",
                                        "index": "analyzed",
                                        "analyzer": "larasearch_text_end_index"
                                      },
                                      "word_start": {
                                        "type": "string",
                                        "index": "analyzed",
                                        "analyzer": "larasearch_word_start_index"
                                      },
                                      "word_middle": {
                                        "type": "string",
                                        "index": "analyzed",
                                        "analyzer": "larasearch_word_middle_index"
                                      },
                                      "word_end": {
                                        "type": "string",
                                        "index": "analyzed",
                                        "analyzer": "larasearch_word_end_index"
                                      }
                                    }
                                  },
                                  "wife": {
                                    "type": "object",
                                    "properties": {
                                      "name": {
                                        "type": "multi_field",
                                        "fields": {
                                          "name": {
                                            "type": "string",
                                            "index": "not_analyzed"
                                          },
                                          "analyzed": {
                                            "type": "string",
                                            "index": "analyzed"
                                          },
                                          "autocomplete": {
                                            "type": "string",
                                            "index": "analyzed",
                                            "analyzer": "larasearch_autocomplete_index"
                                          }
                                        }
                                      },
                                      "children": {
                                        "type": "object",
                                        "properties": {
                                          "name": {
                                            "type": "multi_field",
                                            "fields": {
                                              "name": {
                                                "type": "string",
                                                "index": "not_analyzed"
                                              },
                                              "analyzed": {
                                                "type": "string",
                                                "index": "analyzed"
                                              },
                                              "text_start": {
                                                "type": "string",
                                                "index": "analyzed",
                                                "analyzer": "larasearch_text_start_index"
                                              },
                                              "text_middle": {
                                                "type": "string",
                                                "index": "analyzed",
                                                "analyzer": "larasearch_text_middle_index"
                                              },
                                              "text_end": {
                                                "type": "string",
                                                "index": "analyzed",
                                                "analyzer": "larasearch_text_end_index"
                                              },
                                              "word_start": {
                                                "type": "string",
                                                "index": "analyzed",
                                                "analyzer": "larasearch_word_start_index"
                                              },
                                              "word_middle": {
                                                "type": "string",
                                                "index": "analyzed",
                                                "analyzer": "larasearch_word_middle_index"
                                              },
                                              "word_end": {
                                                "type": "string",
                                                "index": "analyzed",
                                                "analyzer": "larasearch_word_end_index"
                                              }
                                            }
                                          }
                                        }
                                      }
                                    }
                                  }
                                }
                              }
                            },
                            "index": "husband",
                            "type": "Husband"
                          }
                        }', true)
                    ,
                    $params);
            });

        $proxy->shouldReceive('getType')->andReturn('Husband');
        $proxy->shouldReceive('getConfig')->andReturn([
                'autocomplete' => ['name', 'wife.name'],
                'suggest' => ['name'],
                'text_start' => ['name', 'wife.children.name'],
                'text_middle' => ['name', 'wife.children.name'],
                'text_end' => ['name', 'wife.children.name'],
                'word_start' => ['name', 'wife.children.name'],
                'word_middle' => ['name', 'wife.children.name'],
                'word_end' => ['name', 'wife.children.name']
        ]);

        /**
         *
         * Assertion
         *
         */
        $index->create();
    }

    /**
     * @test
     */
    public function it_should_delete_an_index()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('indices->delete')
            ->with(['index' => 'husband'])
            ->andReturn();

        /**
         *
         * Assertion
         *
         */
        $index->delete();
    }

    /**
     * @test
     */
    public function it_should_check_that_an_index_exists()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('indices->exists')
            ->with(['index' => 'husband'])
            ->andReturn();

        /**
         *
         * Assertion
         *
         */
        $index->exists();
    }

    /**
     * @test
     */
    public function it_should_check_that_an_alias_exists()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('indices->existsAlias')
            ->with(['name' => 'Alias'])
            ->andReturn();

        /**
         *
         * Assertion
         *
         */
        $index->aliasExists('Alias');
    }

    /**
     * @test
     */
    public function it_should_store_a_record()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('index')
            ->with([
                'index' => 'husband',
                'type' => 'Husband',
                'id' => 1,
                'body' => 'data'
            ])
            ->andReturn();

        /**
         *
         * Assertion
         *
         */
        $index->store([
            'type' => 'Husband',
            'id' => 1,
            'data' => 'data'
        ]);
    }

    /**
     * @test
     */
    public function it_should_retrieve_a_record()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('get')
            ->with([
                'index' => 'husband',
                'type' => 'Husband',
                'id' => 1,
            ])
            ->andReturn();

        /**
         *
         * Assertion
         *
         */
        $index->retrieve([
            'type' => 'Husband',
            'id' => 1,
            'data' => 'data'
        ]);
    }

    /**
     * @test
     */
    public function it_should_remove_a_record()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('delete')
            ->with([
                'index' => 'husband',
                'type' => 'Husband',
                'id' => 1
            ])
            ->andReturn();

        /**
         *
         * Assertion
         *
         */
        $index->remove([
            'type' => 'Husband',
            'id' => 1
        ]);
    }

    /**
     * @test
     */
    public function it_should_inspect_tokens()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('indices->analyze')
            ->with([
                'index' => 'husband',
                'text' => 'text',
                'option1' => 1,
                'option2' => 2
            ])
            ->andReturn();

        /**
         *
         * Assertion
         *
         */
        $index->tokens('text', [
            'option1' => 1,
            'option2' => 2
        ]);
    }

    /**
     * @test
     */
    public function it_should_get_and_set_params()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();

        $index->setParams(['mock' => 'data']);
        $this->assertEquals(['mock' => 'data'], $index->getParams());
    }

    /**
     * @test
     * @expectedException \Iverberk\Larasearch\Exceptions\ImportException
     */
    public function it_should_store_a_records_in_bulk_with_errors()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();

        /**
         *
         * Expectation
         *
         */
        $proxy->shouldReceive('getType')->andReturn('Husband');

        $client->shouldReceive('bulk')
            ->with([
                'index' => 'husband',
                'type' => 'Husband',
                'body' => 'records'
            ])
            ->andReturn([
                'errors' => true,
                'items' => [
                    [
                        'index' => [
                            'error' => true
                        ]
                    ]
                ]
            ]);

        /**
         *
         * Assertion
         *
         */
        $index->bulk('records');
    }

    /**
     * @test
     */
    public function it_should_store_a_records_in_bulk_without_errors()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();

        /**
         *
         * Expectation
         *
         */
        $proxy->shouldReceive('getType')->andReturn('Husband');

        $client->shouldReceive('bulk')
            ->with([
                'index' => 'husband',
                'type' => 'Husband',
                'body' => 'records'
            ])
            ->andReturn([
                'errors' => false
            ]);

        /**
         *
         * Assertion
         *
         */
        $index->bulk('records');
    }

    /**
     * @test
     */
    public function it_should_clean_old_indices()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();

        // Mock the self::$client variable
        am::double('Iverberk\Larasearch\Index', ['self::$client' => $client]);

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('indices->getAliases')
            ->andReturn([
                'index_123456789101112' => [
                    'aliases' => []
                ]
            ]);

        $client->shouldReceive('indices->delete')
            ->with([
                'index' => 'index_123456789101112'
            ]);

        /**
         *
         * Assertion
         *
         */
        Index::clean('index');
    }

    /**
     * @test
     */
    public function it_should_update_aliases()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();

        // Mock the self::$client variable
        am::double('Iverberk\Larasearch\Index', ['self::$client' => $client]);

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('indices->updateAliases')
            ->with([
                'body' => ['actions' => []]
            ]);

        /**
         *
         * Assertion
         *
         */
        Index::updateAliases(['actions' => []]);
    }

    /**
     * @test
     */
    public function it_should_get_aliases()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();

        // Mock the self::$client variable
        am::double('Iverberk\Larasearch\Index', ['self::$client' => $client]);

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('indices->getAlias')
            ->with([
                'name' => 'mock'
            ]);

        /**
         *
         * Assertion
         *
         */
        Index::getAlias('mock');
    }

    /**
     * @test
     */
    public function it_should_refresh()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $index
         * @var \Mockery\Mock $proxy
         * @var \Mockery\Mock $client
         */
        list($index, $proxy, $client) = $this->getMocks();

        // Mock the self::$client variable
        am::double('Iverberk\Larasearch\Index', ['self::$client' => $client]);

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('indices->refresh')
            ->with([
                'index' => 'mock'
            ]);

        /**
         *
         * Assertion
         *
         */
        Index::refresh('mock');
    }

    /**
     * Construct an Index mock
     *
     * @return array
     */
    private function getMocks($index_prefix = null)
    {
        /**
         *
         * Expectation
         *
         */
        Facade::clearResolvedInstances();
        Config::shouldReceive('get')
            ->with('larasearch::elasticsearch.index_prefix', '')
            ->andReturn($index_prefix);

        $client = m::mock('Elasticsearch\Client');

        App::shouldReceive('make')
            ->with('Elasticsearch')
            ->andReturn($client);

        $proxy = m::mock('Iverberk\Larasearch\Proxy');
        $proxy->shouldReceive('getModel->getTable')
            ->andReturn('Husband');

        $index = m::mock('Iverberk\Larasearch\Index', [$proxy])->makePartial();

        return [$index, $proxy, $client];
    }

}