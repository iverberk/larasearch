<?php namespace Iverberk\Larasearch;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class Proxy {

    /**
     * @var array
     */
    private static $config;

    /**
     * Construct the Elasticsearch proxy based on an Eloquent model
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        self::$config = property_exists($model, '__es_config') ? $model->__es_config : [];

        self::$config['model'] = $model;
        self::$config['index'] = App::make('iverberk.larasearch.index', Utils::findKey(self::$config, 'name', $model->getTable()));
        self::$config['type'] = str_singular($model->getTable());
        self::$config['client'] = App::make('Elasticsearch');
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return self::$config;
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return self::$config['model'];
    }

    /**
     * @return Index
     */
    public function getIndex()
    {
        return self::$config['index'];
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return self::$config['type'];
    }

    /**
     * @return \Elasticsearch\Client
     */
    public function getClient()
    {
        return self::$config['client'];
    }

    /**
     * @param $term
     * @param array $options
     * @return \Iverberk\Larasearch\Response
     */
    public function search($term, $options = [])
    {
        return App::make('iverberk.larasearch.query', ['proxy' => $this, 'term' => $term, 'options' => $options])->execute();
    }

    /**
     * @param bool $force
     * @param bool $relations
     * @param int $batchSize
     * @param callable $callback
     *
     * @return array
     */
    public static function reindex($force = false, $relations = false, $batchSize = 750, Callable $callback = null)
    {
        $analyzers = Config::get('larasearch::elasticsearch.analyzers');
        $params = Config::get('larasearch::elasticsearch.defaults.index');
        $relations = $relations ? with(new Introspect(self::$config['model']))->getPaths() : [];
        $mapping = [];

        if ($force)
        {
            if (self::$config['index']->exists()) self::$config['index']->delete();
        }

        $mapping_options = array_combine(
            $analyzers,
            array_map(function($type)
                {
                    return Utils::findKey(self::$config, $type, false) ?: [];
                },
                $analyzers
            )
        );

        foreach(array_unique(array_flatten(array_values($mapping_options))) as $field)
        {
            // Extract path segments from dot separated field
            $pathSegments = explode('.', $field);

            // Last element is the field name
            $fieldName = array_pop($pathSegments);

            // Apply default field mapping
            $field_mapping = [
                'type' => "multi_field",
                'fields' => [
                    $fieldName => [
                        'type' => 'string',
                        'index' => 'not_analyzed'
                    ],
                    'analyzed' => [
                        'type'=> 'string',
                        'index' => 'analyzed'
                    ]
                ]
            ];

            // Check if we need to add additional mappings
            foreach($mapping_options as $type => $fields)
            {
                if (in_array($field, $fields))
                {
                    $field_mapping['fields'][$type] = [
                        'type' => 'string',
                        'index' => 'analyzed',
                        'analyzer' => "larasearch_${type}_index"
                    ];
                }
            }

            // Check if we are dealing with a nested field
            if(!empty($pathSegments))
            {
                $nested = [];
                $current = array_pop($pathSegments);

                // Create the first level
                $nested[$current] = [
                    'type' => 'object',
                    'properties' => [
                        $fieldName => $field_mapping
                    ]
                ];

                // Add any additonal levels
                foreach(array_reverse($pathSegments) as $pathSegment)
                {
                    $nested[$pathSegment] = [
                        'type' => 'object',
                        'properties' => $nested
                    ];

                    unset($nested[$current]);
                    $current = $pathSegment;
                }

                // Nested field
                $mapping = Utils::array_merge_recursive_distinct($mapping, $nested);
            }
            else
            {
                // Root-level field
                $mapping[$fieldName] = $field_mapping;
            }
        }

        if (!empty($mapping)) $params['mappings']['_default_']['properties'] = $mapping;

        self::$config['index']->create($params);

        $total = self::$config['model']->all()->count();
        $batches = ceil($total / $batchSize);

        for ($batch = 1; $batch <= $batches; $batch++)
        {
            $records = self::$config['model']
                ->with($relations)
                ->skip($batchSize * ($batch-1))
                ->take($batchSize)
                ->get();

            $data = [];

            foreach($records as $record)
            {
                $data[] = [
                    'index' => [
                        '_id' => $record->id
                    ]
                ];

                $data[] = $record->transform();
            }

            $params['body'] = $data;

            $results = self::$config['index']->import(self::$config['type'], $data);

            if ($results['errors'])
            {
                $errorItems = [];

                foreach($results['items'] as $item)
                {
                    if (array_key_exists('error', $item['index']))
                    {
                        $errorItems[] = $item;
                    }
                }

                // Return items with errors
                return [true, $errorItems];
            }

            if (is_callable($callback)) $callback($batch);
        }

        // No items with errors
        return [false, []];
    }

    /**
     * @return bool
     */
    public function shouldIndex()
    {
        return true;
    }

    /**
     * Reindex a specific database record to Elasticsearch
     */
    public function refreshDoc($model)
    {
        try
        {
            self::$config['client']->index(
                [
                    'id' => $model->id,
                    'index' => $this->getIndex()->getName(),
                    'type' => $this->getType(),
                    'body' => $model->transform(true)
                ]
            );
        }
        catch (ModelNotFoundException $e)
        {

        }
    }

}