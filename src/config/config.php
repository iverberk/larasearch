<?php

use Psr\Log\LogLevel;

$compiled = __DIR__ . '/paths.json';

// Check for a json file that contains the compiled paths for model relations
$pathConfig = file_exists($compiled) ? json_decode(file_get_contents($compiled), true) : [];

return array_merge($pathConfig, array(

    'elasticsearch' => [

        /**
         * Configuration array for the low-level Elasticsearch client. See
         * http://www.elasticsearch.org/guide/en/elasticsearch/client/php-api/current/_configuration.html
         * for additional options.
         */

        'params' => [
            'hosts'                 => [ 'localhost:9200' ],
            'connectionClass'       => '\Elasticsearch\Connections\GuzzleConnection',
            'connectionFactoryClass'=> '\Elasticsearch\Connections\ConnectionFactory',
            'connectionPoolClass'   => '\Elasticsearch\ConnectionPool\StaticNoPingConnectionPool',
            'selectorClass'         => '\Elasticsearch\ConnectionPool\Selectors\RoundRobinSelector',
            'serializerClass'       => '\Elasticsearch\Serializers\SmartSerializer',
            'sniffOnStart'          => false,
            'connectionParams'      => [],
            'logging'               => false,
            'logObject'             => null,
            'logPath'               => 'elasticsearch.log',
            'logLevel'              => LogLevel::WARNING,
            'traceObject'           => null,
            'tracePath'             => 'elasticsearch.log',
            'traceLevel'            => LogLevel::WARNING,
            'guzzleOptions'         => [],
            'connectionPoolParams'  => ['randomizeHosts' => true],
            'retries'               => null,
        ],

        'analyzers' => [
            'autocomplete',
            'suggest',
            'text_start',
            'text_middle',
            'text_end',
            'word_start',
            'word_middle',
            'word_end'
        ],

        /**
         * Default configuration array for Elasticsearch indices based on Eloquent models
         * CREDIT: Analyzers, Tokenizers and Filters are copied and renamed from the Searchkick
         * project to get started quickly.
         */

        'defaults' => [
            'index' => [
                'settings' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                    'analysis' => [
                        'analyzer' => [
                            'larasearch_keyword' => [
                                'type' => "custom",
                                'tokenizer' => "keyword",
                                'filter' => ["lowercase", "larasearch_stemmer", "bulgarian_stop", "bulgarian_stemmer"]
                            ],
                            'default_index' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["standard", "lowercase", "asciifolding", "larasearch_index_shingle", "larasearch_stemmer", "bulgarian_stop", "bulgarian_stemmer"]
                            ],
                            'larasearch_search' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["standard", "lowercase", "asciifolding", "larasearch_search_shingle", "larasearch_stemmer", "bulgarian_stop", "bulgarian_stemmer"]
                            ],
                            'larasearch_search2' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["standard", "lowercase", "asciifolding", "larasearch_stemmer", "bulgarian_stop", "bulgarian_stemmer"]
                            ],
                            'larasearch_autocomplete_index' => [
                                'type' => "custom",
                                'tokenizer' => "larasearch_autocomplete_ngram",
                                'filter' => ["lowercase", "asciifolding", "bulgarian_stop", "bulgarian_stemmer"]
                            ],
                            'larasearch_autocomplete_search' => [
                                'type' => "custom",
                                'tokenizer' => "keyword",
                                'filter' => ["lowercase", "asciifolding", "bulgarian_stop", "bulgarian_stemmer"]
                            ],
                            'larasearch_word_search' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["lowercase", "asciifolding", "bulgarian_stop", "bulgarian_stemmer"]
                            ],
                            'larasearch_suggest_index' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["lowercase", "asciifolding", "larasearch_suggest_shingle", "bulgarian_stop", "bulgarian_stemmer"]
                            ],
                            'larasearch_text_start_index' => [
                                'type' => "custom",
                                'tokenizer' => "keyword",
                                'filter' => ["lowercase", "asciifolding", "larasearch_edge_ngram", "bulgarian_stop", "bulgarian_stemmer"]
                            ],
                            'larasearch_text_middle_index' => [
                                'type' => "custom",
                                'tokenizer' => "keyword",
                                'filter' => ["lowercase", "asciifolding", "larasearch_ngram", "bulgarian_stop", "bulgarian_stemmer"]
                            ],
                            'larasearch_text_end_index' => [
                                'type' => "custom",
                                'tokenizer' => "keyword",
                                'filter' => ["lowercase", "asciifolding", "reverse", "larasearch_edge_ngram", "reverse", "bulgarian_stop", "bulgarian_stemmer"]
                            ],
                            'larasearch_word_start_index' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["lowercase", "asciifolding", "larasearch_edge_ngram", "bulgarian_stop", "bulgarian_stemmer"]
                            ],
                            'larasearch_word_middle_index' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["lowercase", "asciifolding", "larasearch_ngram", "bulgarian_stop", "bulgarian_stemmer"]
                            ],
                            'larasearch_word_end_index' => [
                                'type' => "custom",
                                'tokenizer' => "standard",
                                'filter' => ["lowercase", "asciifolding", "reverse", "larasearch_edge_ngram", "reverse", "bulgarian_stop", "bulgarian_stemmer"]
                            ],
                            'bulgarian' => 
                            [
                                'tokenizer' => 'standard',
                                'filter' => 
                                [
                                    'lowercase',
                                    'bulgarian_stop',
                                    'bulgarian_stemmer'
                                ]
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
                            ],
                            'bulgarian_stop' => 
                            [
                                  'type' => 'stop',
                                  'stopwords' => 'а,аз,ако,ала,бе,без,беше,би,бил,била,били,било,близо,бъдат,бъде,бяха,в,вас,ваш,ваша,вероятно,вече,взема,ви,вие,винаги,все,всеки,всички,всичко,всяка,във,въпреки,върху,г,ги,главно,го,д,да,дали,до,докато,докога,дори,досега,доста,е,едва,един,ето,за,зад,заедно,заради,засега,затова,защо,защото,и,из,или,им,има,имат,иска,й,каза,как,каква,какво,както,какъв,като,кога,когато,което,които,кой,който,колко,която,къде,където,към,ли,м,ме,между,мен,ми,мнозина,мога,могат,може,моля,момента,му,н,на,над,назад,най,направи,напред,например,нас,не,него,нея,ни,ние,никой,нито,но,някои,някой,няма,обаче,около,освен,особено,от,отгоре,отново,още,пак,по,повече,повечето,под,поне,поради,после,почти,прави,пред,преди,през,при,пък,първо,с,са,само,се,сега,си,скоро,след,сме,според,сред,срещу,сте,съм,със,също,т,тази,така,такива,такъв,там,твой,те,тези,ти,тн,то,това,тогава,този,той,толкова,точно,трябва,тук,тъй,тя,тях,у,харесва,ч,че,често,чрез,ще,щом,я,a,an,and,are,as,at,be,but,by,for,if,in,into,is,it,no,not,of,on,or,such,that,the,their,then,there,these,they,this,to,was,will,with',
                            ],
                            'bulgarian_stemmer' => 
                            [
                                'type' => 'stemmer',
                                'language' => 'bulgarian'
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
                ],
                'mappings' => [
                    '_default_' => [
                        # https://gist.github.com/kimchy/2898285
                        'dynamic_templates' => [
                            [
                                'string_template' => [
                                    'match' => '*',
                                    'match_mapping_type' => 'string',
                                    'mapping' => [
                                        # http://www.elasticsearch.org/guide/reference/mapping/multi-field-type/
                                        'type' => 'multi_field',
                                        'fields' => [
                                            # analyzed field must be the default field for include_in_all
                                            # http://www.elasticsearch.org/guide/reference/mapping/multi-field-type/
                                            # however, we can include the not_analyzed field in _all
                                            # and the _all index analyzer will take care of it
                                            '{name}' => ['type' => 'string', 'index' => 'not_analyzed'],
                                            'analyzed' => ['type' => 'string', 'index' => 'analyzed']
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
	    
        'index_prefix' => ''
    ]

));
