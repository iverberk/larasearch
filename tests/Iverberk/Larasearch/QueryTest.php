<?php namespace Iverberk\Larasearch;

use Mockery as m;
use AspectMock\Test as am;

class QueryTest extends \PHPUnit_Framework_TestCase {

    protected function tearDown()
    {
        m::close();
        am::clean();
    }

    /**
     * @test
     */
    public function it_should_search_with_term()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $client
         */
        list($proxy, $client, $model) = $this->getMocks();
        $test = $this;

        $query = m::mock('Iverberk\Larasearch\Query', [$proxy, 'term'])->makePartial();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('search')
            ->andReturnUsing(function ($params) use ($test)
            {
                $test->assertEquals(json_decode(
                        '{
                      "index": "Husband",
                      "type": "Husband",
                      "body": {
                        "size": 50,
                        "from": 0,
                        "query": {
                          "dis_max": {
                            "queries": [
                              {
                                "match": {
                                  "_all": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 10,
                                    "analyzer": "larasearch_search"
                                  }
                                }
                              },
                              {
                                "match": {
                                  "_all": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 10,
                                    "analyzer": "larasearch_search2"
                                  }
                                }
                              }
                            ]
                          }
                        }
                      }
                    }', true)
                    ,
                    $params);

                return [];
            });

        /**
         *
         * Assertion
         *
         */
        $response = $query->execute();

        $this->assertInstanceOf('Iverberk\Larasearch\Response', $response);
    }

    /**
     * @test
     */
    public function it_should_search_on_term_with_exact_field()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $client
         */
        list($proxy, $client, $model) = $this->getMocks();
        $test = $this;

        $query = m::mock('Iverberk\Larasearch\Query', [
            $proxy,
            'term',
            [
                'fields' => ['name' => 'exact']
            ]
        ])->makePartial();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('search')
            ->andReturnUsing(function ($params) use ($test)
            {
                $test->assertEquals(json_decode(
                        '{
                      "index": "Husband",
                      "type": "Husband",
                      "body": {
                        "size": 50,
                        "from": 0,
                        "query": {
                          "dis_max": {
                            "queries": [
                              {
                                "match": {
                                  "name": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 1,
                                    "analyzer": "keyword"
                                  }
                                }
                              }
                            ]
                          }
                        }
                      }
                    }', true)
                    ,
                    $params);

                return [];
            });

        /**
         *
         * Assertion
         *
         */
        $response = $query->execute();

        $this->assertInstanceOf('Iverberk\Larasearch\Response', $response);
    }

    /**
     * @test
     */
    public function it_should_search_with_term_and_misspellings()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $client
         */
        list($proxy, $client, $model) = $this->getMocks();
        $test = $this;

        $query = m::mock('Iverberk\Larasearch\Query', [
            $proxy,
            'term',
            [
                'misspellings' => true
            ]
        ])->makePartial();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('search')
            ->andReturnUsing(function ($params) use ($test)
            {
                $test->assertEquals(json_decode(
                        '{
                      "index": "Husband",
                      "type": "Husband",
                      "body": {
                        "size": 50,
                        "from": 0,
                        "query": {
                          "dis_max": {
                            "queries": [
                              {
                                "match": {
                                  "_all": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 10,
                                    "analyzer": "larasearch_search"
                                  }
                                }
                              },
                              {
                                "match": {
                                  "_all": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 10,
                                    "analyzer": "larasearch_search2"
                                  }
                                }
                              },
                              {
                                "match": {
                                  "_all": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 1,
                                    "fuzziness": 1,
                                    "max_expansions": 3,
                                    "analyzer": "larasearch_search"
                                  }
                                }
                              },
                              {
                                "match": {
                                  "_all": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 1,
                                    "fuzziness": 1,
                                    "max_expansions": 3,
                                    "analyzer": "larasearch_search2"
                                  }
                                }
                              }
                            ]
                          }
                        }
                      }
                    }', true),
                    $params);

                return [];
            });

        /**
         *
         * Assertion
         *
         */
        $response = $query->execute();

        $this->assertInstanceOf('Iverberk\Larasearch\Response', $response);
    }

    /**
     * @test
     */
    public function it_should_search_on_fields_with_term()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $client
         */
        list($proxy, $client, $model) = $this->getMocks();
        $test = $this;

        $query = m::mock('Iverberk\Larasearch\Query', [
            $proxy,
            'term',
            [
                'fields' => ['name' => 'word_start', 'wife.name']
            ]
        ])->makePartial();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('search')
            ->andReturnUsing(function ($params) use ($test)
            {
                $test->assertEquals(json_decode(
                        '{
                      "index": "Husband",
                      "type": "Husband",
                      "body": {
                        "size": 50,
                        "from": 0,
                        "query": {
                          "dis_max": {
                            "queries": [
                              {
                                "match": {
                                  "name.word_start": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 1,
                                    "analyzer": "larasearch_word_search"
                                  }
                                }
                              },
                              {
                                "match": {
                                  "wife.name.analyzed": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 10,
                                    "analyzer": "larasearch_search"
                                  }
                                }
                              },
                              {
                                "match": {
                                  "wife.name.analyzed": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 10,
                                    "analyzer": "larasearch_search2"
                                  }
                                }
                              }
                            ]
                          }
                        }
                      }
                    }', true)
                    ,
                    $params);

                return [];
            });

        /**
         *
         * Assertion
         *
         */
        $response = $query->execute();

        $this->assertInstanceOf('Iverberk\Larasearch\Response', $response);
    }

    /**
     * @test
     */
    public function it_should_search_on_term_with_autocomplete()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $client
         */
        list($proxy, $client, $model) = $this->getMocks();
        $test = $this;

        $query = m::mock('Iverberk\Larasearch\Query', [
            $proxy,
            'term',
            [
                'autocomplete' => true
            ]
        ])->makePartial();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('search')
            ->andReturnUsing(function ($params) use ($test)
            {
                $test->assertEquals(json_decode(
                        '{
                      "index": "Husband",
                      "type": "Husband",
                      "body": {
                        "size": 50,
                        "from": 0,
                        "query": {
                          "multi_match": {
                            "fields": [
                              "name.autocomplete",
                              "wife.name.autocomplete"
                            ],
                            "query": "term",
                            "analyzer": "larasearch_autocomplete_search"
                          }
                        }
                      }
                    }', true)
                    ,
                    $params);

                return [];
            });

        /**
         *
         * Assertion
         *
         */
        $response = $query->execute();

        $this->assertInstanceOf('Iverberk\Larasearch\Response', $response);
    }

    /**
     * @test
     */
    public function it_should_search_on_fields_with_term_and_autocomplete()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $client
         */
        list($proxy, $client, $model) = $this->getMocks();
        $test = $this;

        $query = m::mock('Iverberk\Larasearch\Query', [
            $proxy,
            'term',
            [
                'fields' => ['wife.name'],
                'autocomplete' => true
            ]
        ])->makePartial();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('search')
            ->andReturnUsing(function ($params) use ($test)
            {
                $test->assertEquals(json_decode(
                        '{
                          "index": "Husband",
                          "type": "Husband",
                          "body": {
                            "size": 50,
                            "from": 0,
                            "query": {
                              "multi_match": {
                                "fields": [
                                  "wife.name.autocomplete"
                                ],
                                "query": "term",
                                "analyzer": "larasearch_autocomplete_search"
                              }
                            }
                          }
                        }', true)
                    ,
                    $params);

                return [];
            });

        /**
         *
         * Assertion
         *
         */
        $response = $query->execute();

        $this->assertInstanceOf('Iverberk\Larasearch\Response', $response);
    }

    /**
     * @test
     */
    public function it_should_search_on_fields_with_term_and_select()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $client
         */
        list($proxy, $client, $model) = $this->getMocks();
        $test = $this;

        $query = m::mock('Iverberk\Larasearch\Query', [
            $proxy,
            'term',
            [
                'fields' => ['wife.name'],
                'select' => ['name']
            ]
        ])->makePartial();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('search')
            ->andReturnUsing(function ($params) use ($test)
            {
                $test->assertEquals(json_decode(
                        '{
                      "index": "Husband",
                      "type": "Husband",
                      "body": {
                        "size": 50,
                        "from": 0,
                        "query": {
                          "dis_max": {
                            "queries": [
                              {
                                "match": {
                                  "wife.name.analyzed": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 10,
                                    "analyzer": "larasearch_search"
                                  }
                                }
                              },
                              {
                                "match": {
                                  "wife.name.analyzed": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 10,
                                    "analyzer": "larasearch_search2"
                                  }
                                }
                              }
                            ]
                          }
                        },
                        "fields": [
                          "name"
                        ]
                      }
                    }', true)
                    ,
                    $params);

                return [];
            });

        /**
         *
         * Assertion
         *
         */
        $response = $query->execute();

        $this->assertInstanceOf('Iverberk\Larasearch\Response', $response);
    }

    /**
     * @test
     */
    public function it_should_search_on_fields_with_term_and_load()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $client
         */
        list($proxy, $client, $model) = $this->getMocks();
        $test = $this;

        $query = m::mock('Iverberk\Larasearch\Query', [
            $proxy,
            'term',
            [
                'fields' => ['wife.name'],
                'load' => ['name']
            ]
        ])->makePartial();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('search')
            ->andReturnUsing(function ($params) use ($test)
            {
                $test->assertEquals(json_decode(
                        '{
                          "index": "Husband",
                          "type": "Husband",
                          "body": {
                            "size": 50,
                            "from": 0,
                            "query": {
                              "dis_max": {
                                "queries": [
                                  {
                                    "match": {
                                      "wife.name.analyzed": {
                                        "query": "term",
                                        "operator": "and",
                                        "boost": 10,
                                        "analyzer": "larasearch_search"
                                      }
                                    }
                                  },
                                  {
                                    "match": {
                                      "wife.name.analyzed": {
                                        "query": "term",
                                        "operator": "and",
                                        "boost": 10,
                                        "analyzer": "larasearch_search2"
                                      }
                                    }
                                  }
                                ]
                              }
                            },
                            "fields": [
                              "name"
                            ]
                          }
                        }', true)
                    ,
                    $params);

                return [];
            });

        /**
         *
         * Assertion
         *
         */
        $response = $query->execute();

        $this->assertInstanceOf('Iverberk\Larasearch\Response', $response);
    }

    /**
     * @test
     */
    public function it_should_search_on_fields_and_highlight()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $client
         */
        list($proxy, $client, $model) = $this->getMocks();
        $test = $this;

        $query = m::mock('Iverberk\Larasearch\Query', [
            $proxy,
            'term',
            [
                'fields' => ['name'],
                'highlight' => [
                    'tag' => '<b>'
                ]
            ]
        ])->makePartial();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('search')
            ->andReturnUsing(function ($params) use ($test)
            {
                $query = json_decode(
                    '{
                      "index": "Husband",
                      "type": "Husband",
                      "body": {
                        "size": 50,
                        "from": 0,
                        "highlight": {
                          "fields": {
                            "name.analyzed": {}
                          },
                          "pre_tags": [
                            "<b>"
                          ],
                          "post_tags": [
                            "<\/b>"
                          ]
                        },
                        "query": {
                          "dis_max": {
                            "queries": [
                              {
                                "match": {
                                  "name.analyzed": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 10,
                                    "analyzer": "larasearch_search"
                                  }
                                }
                              },
                              {
                                "match": {
                                  "name.analyzed": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 10,
                                    "analyzer": "larasearch_search2"
                                  }
                                }
                              }
                            ]
                          }
                        }
                      }
                    }', true);

                // Mark explicit stdclass
                $query['body']['highlight']['fields']['name.analyzed'] = new \StdClass;
                $test->assertEquals(
                    $query,
                    $params);

                return [];
            });

        /**
         *
         * Assertion
         *
         */
        $response = $query->execute();

        $this->assertInstanceOf('Iverberk\Larasearch\Response', $response);
    }

    /**
     * @test
     */
    public function it_should_search_on_fields_with_suggestions()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $client
         */
        list($proxy, $client, $model) = $this->getMocks();
        $test = $this;

        $query = m::mock('Iverberk\Larasearch\Query', [
            $proxy,
            'term',
            [
                'fields' => ['name'],
                'suggest' => true
            ]
        ])->makePartial();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('search')
            ->andReturnUsing(function ($params) use ($test)
            {
                $test->assertEquals(json_decode(
                        '{
                      "index": "Husband",
                      "type": "Husband",
                      "body": {
                        "size": 50,
                        "from": 0,
                        "suggest": {
                          "text": "term",
                          "name": {
                            "phrase": {
                              "field": "name.suggest"
                            }
                          }
                        },
                        "query": {
                          "dis_max": {
                            "queries": [
                              {
                                "match": {
                                  "name.analyzed": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 10,
                                    "analyzer": "larasearch_search"
                                  }
                                }
                              },
                              {
                                "match": {
                                  "name.analyzed": {
                                    "query": "term",
                                    "operator": "and",
                                    "boost": 10,
                                    "analyzer": "larasearch_search2"
                                  }
                                }
                              }
                            ]
                          }
                        }
                      }
                    }', true),
                    $params);

                return [];
            });

        /**
         *
         * Assertion
         *
         */
        $response = $query->execute();

        $this->assertInstanceOf('Iverberk\Larasearch\Response', $response);
    }

    /**
     * @test
     */
    public function it_should_search_with_aggregations()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $client
         */
        list($proxy, $client, $model) = $this->getMocks();
        $test = $this;

        $query = m::mock('Iverberk\Larasearch\Query', [
            $proxy,
            '*',
            [
                'aggs' => [
                    'agg_name' => [
                        'type' => 'terms',
                        'field' => 'name'
                    ]
                ]
            ]
        ])->makePartial();

        /**
         *
         * Expectation
         *
         */
        $client->shouldReceive('search')
            ->andReturnUsing(function ($params) use ($test)
            {
                $test->assertEquals(json_decode(
                        '{
                      "index": "Husband",
                      "type": "Husband",
                      "body": {
                        "size": 50,
                        "from": 0,
                        "aggs": {
                          "agg_name": {
                            "terms": {
                              "field": "name",
                              "size": 0
                            }
                          }
                        },
                        "query": {
                          "match_all": [

                          ]
                        }
                      }
                    }', true),
                    $params);

                return [];
            });

        /**
         *
         * Assertion
         *
         */
        $response = $query->execute();

        $this->assertInstanceOf('Iverberk\Larasearch\Response', $response);
    }

    /**
     * @test
     */
    public function it_should_search_with_sort()
    {
        /**
         * Set
         * @var \Mockery\Mock $client
         */
        list($proxy, $client, $model) = $this->getMocks();
        $test = $this;

        $query = m::mock('Iverberk\Larasearch\Query', [$proxy, 'term', ['sort' => 'name']])->makePartial();

        /**
         * Expectation
         */

        $client->shouldReceive('search')
            ->andReturnUsing(function ($params) use ($test)
            {
                $test->assertEquals(json_decode(
                    '{
                        "index": "Husband",
                        "type": "Husband",
                        "body": {
                            "size": 50,
                            "from": 0,
                            "sort": "name",
                            "query": {
                                "dis_max": {
                                    "queries": [
                                        {
                                            "match": {
                                                "_all": {
                                                    "query": "term",
                                                    "operator": "and",
                                                    "boost": 10,
                                                    "analyzer": "larasearch_search"
                                                }
                                            }
                                        },
                                        {
                                            "match": {
                                                "_all": {
                                                    "query": "term",
                                                    "operator": "and",
                                                    "boost": 10,
                                                    "analyzer": "larasearch_search2"
                                                }
                                            }
                                        }
                                    ]
                                }
                            }
                        }
                    }', true), $params);
                return [];
            });

        /**
         * Assertion
         */
        $response = $query->execute();

        $this->assertInstanceOf('Iverberk\Larasearch\Response', $response);
    }


    /**
     * @test
     * @expectedException \InvalidArgumentException
     */
    public function it_should_throw_an_exception_when_multiple_queries_are_provided()
    {
        /**
         *
         * Set
         *
         * @var \Mockery\Mock $client
         */
        list($proxy, $client, $model) = $this->getMocks();
        $test = $this;

        $query = m::mock('Iverberk\Larasearch\Query', [
            $proxy,
            '*',
            [
                'json' => '{}',
                'query' => []
            ]
        ])->makePartial();

        /**
         *
         * Assertion
         *
         */
        $query->execute();
    }

    /**
     * Construct an helper mocks
     *
     * @return array
     */
    private function getMocks()
    {
        $client = m::mock('Elasticsearch\Client');
        $model = m::mock('Illuminate\Database\Eloquent\Model');

        $proxy = m::mock('Iverberk\Larasearch\Proxy');
        $proxy->shouldReceive('getClient')
            ->andReturn($client);
        $proxy->shouldReceive('getModel')
            ->andReturn($model);
        $proxy->shouldReceive('getIndex->getName')
            ->andReturn('Husband');
        $proxy->shouldReceive('getType')
            ->andReturn('Husband');
        $proxy->shouldReceive('getConfig')
            ->andReturn([
                'autocomplete' => ['name', 'wife.name'],

                'suggest' => ['name'],

                'text_start' => ['name', 'wife.children.name'],
                'text_middle' => ['name', 'wife.children.name'],
                'text_end' => ['name', 'wife.children.name'],

                'word_start' => ['name', 'wife.children.name'],
                'word_middle' => ['name', 'wife.children.name'],
                'word_end' => ['name', 'wife.children.name']
            ]);

        return [$proxy, $client, $model];
    }

}
