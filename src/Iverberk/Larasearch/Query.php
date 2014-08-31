<?php namespace Iverberk\Larasearch;

use stdClass;

class Query {

	/**
	 * @var Proxy
	 */
	private $proxy;

	/**
	 * @var string
	 */
	private $term;

	/**
	 * @var array
	 */
	private $options;

	/**
	 * @var array
	 */
	private $fields;

	/**
	 * @var array
	 */
	private $payload;

	/**
	 * @var array
	 */
	private $pagination;

	/**
	 * @param Proxy $proxy
	 * @param       $term
	 * @param array $options
	 */
	public function __construct(Proxy $proxy, $term, $options = [])
	{
		$this->proxy = $proxy;
		$this->term = $term;
		$this->options = $options;
	}

	/**
	 *
	 */
	private function getAggregations()
	{
		if ($aggregations = Utils::findKey($this->options, 'aggs', false))
		{
			foreach ($aggregations as $name => $aggregation)
			{
				switch ($aggregation['type'])
				{
					case 'terms':
						$this->payload['aggs'][$name]['terms'] = ['field' => $aggregation['field'], 'size' => 0];
						break;
				}
			}
		}
	}

	/**
	 *
	 */
	private function getFields()
	{
		if (array_key_exists('fields', $this->options))
		{
			if (array_key_exists('autocomplete', $this->options))
			{
				$this->fields = array_map(function ($field)
					{
						return "${field}.autocomplete";
					},
					$this->options['fields']
				);
			}
			else
			{
				foreach ($this->options['fields'] as $key => $value)
				{
					if (is_string($key))
					{
						$k = $key;
						$v = $value;
					}
					else
					{
						// $key is the numerical index, so use $value as key
						$k = $value;
						$v = 'word';
					}
					$this->fields[] = "$k." . (($v == 'word') ? 'analyzed' : $v);
				}

			}
		}
		else
		{
			if (array_key_exists('autocomplete', $this->options))
			{
				$this->fields = array_map(function ($field)
					{
						return "${field}.autocomplete";
					},
					Utils::findKey($this->proxy->getConfig(), 'autocomplete', []));
			}
			else
			{
				$this->fields = ['_all'];
			}
		}
	}

	/**
	 * Add requested pagination parameters to the payload
	 */
	private function getPagination()
	{
		# pagination
		$this->pagination['page'] = 1;
		$this->pagination['per_page'] = Utils::findKey($this->options, 'limit', 50);
		$this->pagination['padding'] = Utils::findKey($this->options, 'padding', 0);
		$this->pagination['offset'] = Utils::findKey($this->options, 'offset', 0);

		$this->payload['size'] = $this->pagination['per_page'];
		$this->payload['from'] = $this->pagination['offset'];
	}

	/**
	 * Add requested highlights to the payload
	 */
	private function getHighlight()
	{
		if (Utils::findKey($this->options, 'highlight', false))
		{
			foreach ($this->fields as $field)
			{
				$this->payload['highlight']['fields'][$field] = new StdClass();
			}

			if ($tag = Utils::findKey($this->options['highlight'], 'tag', false))
			{
				$this->payload['highlight']['pre_tags'] = [$tag];
				$this->payload['highlight']['post_tags'] = [preg_replace('/\A</', '</', $tag)];
			}
		}
	}

	/**
	 * Add requested suggestions to the payload
	 */
	private function getSuggest()
	{
		if ($suggestions = Utils::findKey($this->options, 'suggest', false))
		{
			$suggest_fields = Utils::findKey($this->proxy->getConfig(), 'suggest', []);

			if ($fields = Utils::findKey($this->options, 'fields', false))
			{
				$suggest_fields = array_intersect($suggest_fields, $fields);
			}

			if (!empty($suggest_fields))
			{
				$this->payload['suggest'] = ['text' => $this->term];
				foreach ($suggest_fields as $field)
				{
					$this->payload['suggest'][$field] = ['phrase' => ['field' => "${field}.suggest"]];
				}
			}
		}
	}

	/**
	 * Construct the payload from the options
	 */
	private function getPayload()
	{
		$payloads = [
			'json' => Utils::findKey($this->options, 'json', false),
			'query' => Utils::findKey($this->options, 'query', false),
			'similar' => [
				'more_like_this' => [
					'fields' => $this->fields,
					'like_text' => $this->term,
					'min_doc_freq' => 1,
					'min_term_freq' => 1,
					'analyzer' => "larasearch_search2"
				]
			],
			'autocomplete' => [
				'multi_match' => [
					'fields' => $this->fields,
					'query' => $this->term,
					'analyzer' => "larasearch_autocomplete_search"
				]
			]
		];

		// Find the correct payload based on the options
		$payload_key = array_intersect_key($this->options, $payloads);

		$operator = Utils::findKey($this->options, 'operator', 'and');

		if (count($payload_key) == 1)
		{
			$payload = $payloads[key($payload_key)];
		}
		elseif (count($payload_key) == 0)
		{
			if ($this->term == '*')
			{
				$payload = ['match_all' => []];
			}
			else
			{
				$queries = [];

				foreach ($this->fields as $field)
				{
					$qs = [];

					$shared_options = [
						'query' => $this->term,
						'operator' => $operator,
						'boost' => 1
					];

					if ($field == '_all' || substr_compare($field, '.analyzed', -9, 9) === 0)
					{
						$qs = array_merge($qs, [
								array_merge($shared_options, ['boost' => 10, 'analyzer' => "larasearch_search"]),
								array_merge($shared_options, ['boost' => 10, 'analyzer' => "larasearch_search2"])
							]
						);
						if ($misspellings = Utils::findKey($this->options, 'misspellings', false))
						{
							$distance = 1;
							$qs = array_merge($qs, [
									array_merge($shared_options, ['fuzziness' => $distance, 'max_expansions' => 3, 'analyzer' => "larasearch_search"]),
									array_merge($shared_options, ['fuzziness' => $distance, 'max_expansions' => 3, 'analyzer' => "larasearch_search2"])
								]
							);

						}
					}
					elseif (substr_compare($field, '.exact', -6, 6) === 0)
					{
						$f = substr($field, 0, -6);
						$queries[] = [
							'match' => [
								$f => array_merge($shared_options, ['analyzer' => 'keyword'])
							]
						];
					}
					else
					{
						$analyzer = preg_match('/\.word_(start|middle|end)\z/', $field) ? "larasearch_word_search" : "larasearch_autocomplete_search";
						$qs[] = array_merge($shared_options, ['analyzer' => $analyzer]);
					}

					$queries = array_merge($queries, array_map(function ($q) use ($field)
							{
								return ['match' => [$field => $q]];
							}, $qs));
				}

				$payload = ['dis_max' => ['queries' => $queries]];
			}
		}
		else
		{
			// We have multiple query definitions, so abort
			throw new \InvalidArgumentException('Cannot use multiple query definitions.');
		}

		$this->payload['query'] = $payload;

		if ($load = Utils::findKey($this->options, 'load', false))
		{
			$this->payload['fields'] = is_array($load) ? $load : [];
		}
		elseif ($select = Utils::findKey($this->options, 'select', false))
		{
			$this->payload['fields'] = $select;
		}
	}

	/**
	 * Execute the query and return the response in a rich wrapper class
	 *
	 * @return Response
	 */
	public function execute()
	{
		$this->getFields();
		$this->getPagination();
		$this->getHighlight();
		$this->getSuggest();
		$this->getAggregations();
		$this->getPayload();

		$params = [
			'index' => Utils::findKey($this->options, 'index', false) ?: $this->proxy->getIndex()->getName(),
			'type' => Utils::findKey($this->options, 'type', false) ?: $this->proxy->getType(),
			'body' => $this->payload
		];

		return new Response($this->proxy->getModel(), $this->proxy->getClient()->search($params));
	}

}