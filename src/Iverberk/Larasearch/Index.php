<?php namespace Iverberk\Larasearch;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use Iverberk\Larasearch\Exceptions\ImportException;

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
    private static $client;

    /**
     * Laravel config repository abstraction
     *
     * @var \Iverberk\Larasearch\Config
     */
    private static $config;

    /**
     * Index parameters
     *
     * @var array
     */
    private $params;

    /**
     * Larasearch Eloquent proxy
     *
     * @var Proxy
     */
    private $proxy;

    /**
     * Retrieve the ElasticSearch Client
     *
     * @return \Elasticsearch\Client
     */
    private static function getClient()
    {
        return self::$client;
    }


    /**
     * @param string $name
     * @param Proxy $proxy
     */
    public function __construct(Proxy $proxy, $name = '')
    {
        self::$client = App::make('Elasticsearch');
        self::$config = App::make('iverberk.larasearch.config');

        $this->setProxy($proxy);
        $this->setName($name ?: $proxy->getModel()->getTable());
    }

    protected static function getConfig()
    {
        return self::$config;
    }

    /**
     * Import an Eloquent
     *
     * @param Model $model
     * @param array $relations
     * @param int $batchSize
     * @param callable $callback
     * @internal param $type
     */
    public function import(Model $model, $relations = [], $batchSize = 750, Callable $callback = null)
    {
        $batch = 0;

        while (true)
        {
            // Increase the batch number
            $batch += 1;

            // Load records from the database
            $records = $model
                ->with($relations)
                ->skip($batchSize * ($batch - 1))
                ->take($batchSize)
                ->get();

            // Break out of the loop if we are out of records
            if (count($records) == 0) break;

            // Call the callback function to provide feedback on the import process
            if ($callback)
            {
                $callback($batch);
            }

            // Transform each record before sending it to Elasticsearch
            $data = [];

            foreach ($records as $record)
            {
                $data[] = [
                    'index' => [
                        '_id' => $record->getEsId()
                    ]
                ];

                $data[] = $record->transform(!empty($relations));
            }

            // Bulk import the data to Elasticsearch
            $this->bulk($data);
        }
    }

    /**
     * Set index name
     *
     * @param string
     * @return Index
     */
    public function setName($name)
    {
        $index_prefix = self::getConfig()->get('elasticsearch.index_prefix', '');
        if ($index_prefix && !Str::startsWith($name, $index_prefix)) $name = $index_prefix . $name;

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
        return strtolower($this->name);
    }

    /**
     * Set ElasticSearch Proxy for the index
     *
     * @param Proxy $proxy
     * @return \Iverberk\Larasearch\Proxy
     * @author Chris Nagle
     */
    public function setProxy(Proxy $proxy)
    {
        $this->proxy = $proxy;

        return $proxy;
    }

    /**
     * Get ElasticSearch Proxy for the index
     *
     * @return \Iverberk\Larasearch\Proxy
     */
    public function getProxy()
    {
        return $this->proxy;
    }

    /**
     * Create a new index
     *
     * @param array $options
     */
    public function create($options = [])
    {
        $body = empty($options) ? $this->getDefaultIndexParams() : $options;

        self::getClient()->indices()->create(['index' => $this->getName(), 'body' => $body]);
    }

    /**
     * Delete an index
     */
    public function delete()
    {
        self::getClient()->indices()->delete(['index' => $this->getName()]);
    }

    /**
     * Check if an index exists
     *
     * @return bool
     */
    public function exists()
    {
        return self::getClient()->indices()->exists(['index' => $this->getName()]);
    }

    /**
     * Check if an alias exists
     *
     * @param $alias
     * @return bool
     */
    public function aliasExists($alias)
    {
        $index_prefix = self::getConfig()->get('elasticsearch.index_prefix', '');
        if ($index_prefix && !Str::startsWith($alias, $index_prefix)) $alias = $index_prefix . $alias;

        return self::getClient()->indices()->existsAlias(['name' => $alias]);
    }

    /**
     * Store a record in the index
     *
     * @param $record
     */
    public function store($record)
    {
        $params['index'] = $this->getName();
        $params['type'] = $record['type'];
        $params['id'] = $record['id'];
        $params['body'] = $record['data'];

        self::getClient()->index($params);
    }

    /**
     * Retrieve a record from the index
     *
     * @param $record
     */
    public function retrieve($record)
    {
        $params['index'] = $this->getName();
        $params['type'] = $record['type'];
        $params['id'] = $record['id'];

        self::getClient()->get($params);
    }

    /**
     * Remove a record from the index
     *
     * @param $record
     */
    public function remove($record)
    {
        $params['index'] = $this->getName();
        $params['type'] = $record['type'];
        $params['id'] = $record['id'];

        self::getClient()->delete($params);
    }

    /**
     * Inspect tokens returned from the analyzer
     *
     * @param string $text
     * @param array $options
     */
    public function tokens($text, $options = [])
    {
        self::getClient()->indices()->analyze(array_merge(['index' => $this->getName(), 'text' => $text], $options));
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @param $records
     * @throws ImportException
     */
    public function bulk($records)
    {
        $params['index'] = $this->getName();
        $params['type'] = $this->getProxy()->getType();
        $params['body'] = $records;

        $results = self::getClient()->bulk($params);

        if ($results['errors'])
        {
            $errorItems = [];

            foreach ($results['items'] as $item)
            {
                if (array_key_exists('error', $item['index']))
                {
                    $errorItems[] = $item;
                }
            }

            throw new ImportException('Bulk import with errors', 1, $errorItems);
        }
    }

    /**
     * Clean old indices that start with $name
     *
     * @param $name
     */
    public static function clean($name)
    {
        $index_prefix = self::getConfig()->get('elasticsearch.index_prefix', '');
        if ($index_prefix && !Str::startsWith($name, $index_prefix)) $name = $index_prefix . $name;

        $indices = self::getClient()->indices()->getAliases();
        foreach ($indices as $index => $value)
        {
            if (empty($value['aliases']) && preg_match("/^${name}_\\d{14,17}$/", $index))
            {
                self::getClient()->indices()->delete(['index' => $index]);
            }
        }
    }

    /**
     * Retrieve aliases
     *
     * @param $name
     * @return array
     */
    public static function getAlias($name)
    {
        $index_prefix = self::getConfig()->get('elasticsearch.index_prefix', '');
        if ($index_prefix && !Str::startsWith($name, $index_prefix)) $name = $index_prefix . $name;

        return self::getClient()->indices()->getAlias(['name' => $name]);
    }

    /**
     * @param array $actions
     * @return array
     */
    public static function updateAliases(array $actions)
    {
        if (isset($actions['actions']) && ($index_prefix = self::getConfig()->get('elasticsearch.index_prefix', '')))
        {
            foreach ($actions['actions'] as &$action)
            {
                list($verb, $data) = each($action);
                if (!Str::startsWith($data['index'], $index_prefix)) $action[$verb]['index'] = $index_prefix . $data['index'];
                if (!Str::startsWith($data['alias'], $index_prefix)) $action[$verb]['alias'] = $index_prefix . $data['alias'];
            }
        }

        return self::getClient()->indices()->updateAliases(['body' => $actions]);
    }

    /**
     * Refresh an index
     *
     * @param $index
     * @return array
     */
    public static function refresh($index)
    {
        $index_prefix = self::getConfig()->get('elasticsearch.index_prefix', '');
        if ($index_prefix && !Str::startsWith($index, $index_prefix)) $index = $index_prefix . $index;

        return self::getClient()->indices()->refresh(['index' => $index]);
    }

    /**
     * Initialize the default index settings and mappings
     *
     * @return array
     */
    private function getDefaultIndexParams()
    {
        $analyzers = self::getConfig()->get('elasticsearch.analyzers');
        $params = self::getConfig()->get('elasticsearch.defaults.index');
        $mapping = [];

        $mapping_options = array_combine(
            $analyzers,
            array_map(function ($type)
                {
                    $config = $this->getProxy()->getConfig();

                    // Maintain backwards compatibility by allowing a plain array of analyzer => fields
                    $field_mappings = Utils::findKey($config, $type, false) ?: [];

                    // Also read from a dedicated array key called 'analyzers'
                    if (isset($config['analyzers']))
                    {
                        $field_mappings = array_merge($field_mappings, Utils::findKey($config['analyzers'], $type, false) ?: []);
                    }

                    return $field_mappings;
                },
                $analyzers
            )
        );

        foreach (array_unique(array_flatten(array_values($mapping_options))) as $field)
        {
            // Extract path segments from dot separated field
            $pathSegments = explode('.', $field);

            // Last element is the field name
            $fieldName = array_pop($pathSegments);

            // Apply default field mapping
            $fieldMapping = [
                'type' => "multi_field",
                'fields' => [
                    $fieldName => [
                        'type' => 'string',
                        'index' => 'not_analyzed'
                    ],
                    'analyzed' => [
                        'type' => 'string',
                        'index' => 'analyzed'
                    ]
                ]
            ];

            // Check if we need to add additional mappings
            foreach ($mapping_options as $type => $fields)
            {
                if (in_array($field, $fields))
                {
                    $fieldMapping['fields'][$type] = [
                        'type' => 'string',
                        'index' => 'analyzed',
                        'analyzer' => "larasearch_${type}_index"
                    ];
                }
            }

            if (!empty($pathSegments))
            {
                $mapping = Utils::array_merge_recursive_distinct(
                    $mapping,
                    $this->getNestedFieldMapping($fieldName, $fieldMapping, $pathSegments)
                );
            } else
            {
                $mapping[$fieldName] = $fieldMapping;
            }
        }

        if (!empty($mapping)) $params['mappings']['_default_']['properties'] = $mapping;

        $params['index'] = $this->getName();
        $params['type'] = $this->getProxy()->getType();

        return $params;
    }

    /**
     * @param $fieldName
     * @param $fieldMapping
     * @param $pathSegments
     * @return array
     */
    private function getNestedFieldMapping($fieldName, $fieldMapping, $pathSegments)
    {
        $nested = [];
        $current = array_pop($pathSegments);

        // Create the first level
        $nested[$current] = [
            'type' => 'object',
            'properties' => [
                $fieldName => $fieldMapping
            ]
        ];

        // Add any additional levels
        foreach (array_reverse($pathSegments) as $pathSegment)
        {
            $nested[$pathSegment] = [
                'type' => 'object',
                'properties' => $nested
            ];

            unset($nested[$current]);
            $current = $pathSegment;
        }

        return $nested;
    }

}
