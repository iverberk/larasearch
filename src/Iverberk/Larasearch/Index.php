<?php namespace Iverberk\Larasearch;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
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
	 * @param string $name
	 * @param Proxy  $proxy
	 */
	public function __construct(Proxy $proxy, $name = '')
	{
		self::$client = App::make('Elasticsearch');

		$this->proxy = $proxy;
		$this->name = $name ?: $proxy->getModel()->getTable();
	}

	/**
	 * Import an Eloquent
	 *
	 * @param Model    $model
	 * @param array    $relations
	 * @param int      $batchSize
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
						'_id' => $record->id
					]
				];

				$data[] = $record->transform();
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
        if (!is_null($this->name))
        {
            return Config::get('larasearch::elasticsearch.index_prefix', '') . $this->name;
        }
	}

	/**
	 * Create a new index
	 *
	 * @param array $options
	 */
	public function create($options = [])
	{
		$body = empty($options) ? $this->getDefaultIndexParams() : $options;

		self::$client->indices()->create(['index' => $this->getName(), 'body' => $body]);
	}

	/**
	 * Delete an index
	 */
	public function delete()
	{
		self::$client->indices()->delete(['index' => $this->getName()]);
	}

	/**
	 * Check if an index exists
	 *
	 * @return bool
	 */
	public function exists()
	{
		return self::$client->indices()->exists(['index' => $this->getName()]);
	}

	/**
	 * Check if an alias exists
	 *
	 * @param $alias
	 * @return bool
	 */
	public function aliasExists($alias)
	{
		return self::$client->indices()->existsAlias(['name' => $alias]);
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

		self::$client->index($params);
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

		self::$client->get($params);
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

		self::$client->delete($params);
	}

	/**
	 * Inspect tokens returned from the analyzer
	 *
	 * @param string $text
	 * @param array  $options
	 */
	public function tokens($text, $options = [])
	{
		self::$client->indices()->analyze(array_merge(['index' => $this->getName(), 'text' => $text], $options));
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
		$params['type'] = $this->proxy->getType();
		$params['body'] = $records;

		$results = self::$client->bulk($params);

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
        $index_prefix = Config::get('larasearch::elasticsearch.index_prefix', '');
		if ($index_prefix && !Str::startsWith($name, $index_prefix)) $name = $index_prefix . $name;

        $indices = self::$client->indices()->getAliases();

		foreach ($indices as $index => $value)
		{
			if (empty($value['aliases']) && preg_match("/^${name}_\\d{14,17}$/", $index))
			{
				self::$client->indices()->delete(['index' => $index]);
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
        $index_prefix = Config::get('larasearch::elasticsearch.index_prefix', '');
        if ($index_prefix && !Str::startsWith($name, $index_prefix)) $name = $index_prefix . $name;

        return self::$client->indices()->getAlias(['name' => $name]);
	}

	/**
	 * @param array $actions
	 * @return array
	 */
	public static function updateAliases(array $actions)
	{
        if (isset($actions['actions']) && ($index_prefix = Config::get('larasearch::elasticsearch.index_prefix', '')))
        {
            foreach ($actions['actions'] as &$action)
            {
                list($verb, $data) = each($action);
                $action[$verb]['index'] = $index_prefix . $data['index'];
                $action[$verb]['alias'] = $index_prefix . $data['alias'];
            }
        }
		return self::$client->indices()->updateAliases(['body' => $actions]);
	}

	/**
	 * Refresh an index
	 *
	 * @param $index
	 * @return array
	 */
	public static function refresh($index)
	{
        $index_prefix = Config::get('larasearch::elasticsearch.index_prefix', '');
        if ($index_prefix && !Str::startsWith($index, $index_prefix)) $index = $index_prefix . $index;

        return self::$client->indices()->refresh(['index' => $index]);
	}

	/**
	 * Initialize the default index settings and mappings
	 *
	 * @return array
	 */
	private function getDefaultIndexParams()
	{
		$analyzers = Config::get('larasearch::elasticsearch.analyzers');
		$params = Config::get('larasearch::elasticsearch.defaults.index');
		$mapping = [];

		$mapping_options = array_combine(
			$analyzers,
			array_map(function ($type)
				{
					return Utils::findKey($this->proxy->getConfig(), $type, false) ?: [];
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
			}
			else
			{
				$mapping[$fieldName] = $fieldMapping;
			}
		}

		if (!empty($mapping)) $params['mappings']['_default_']['properties'] = $mapping;

		$params['index'] = $this->getName();
		$params['type'] = $this->proxy->getType();

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