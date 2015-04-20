<?php namespace Iverberk\Larasearch;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class Proxy {

    /**
     * @var array
     */
    private $config;

    /**
     * Construct the Elasticsearch proxy based on an Eloquent model
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $class = get_class($model);

        $this->config = property_exists($class, '__es_config') ? $class::$__es_config : [];

        $this->config['model'] = $model;
        $this->config['type'] = str_singular($model->getTable());

        $this->config['client'] = App::make('Elasticsearch');
        $this->config['index'] = App::make('iverberk.larasearch.index', array('proxy' => $this));
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return Model
     */
    public function getModel()
    {
        return $this->config['model'];
    }

    /**
     * @return Index
     */
    public function getIndex()
    {
        return $this->config['index'];
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->config['type'];
    }

    /**
     * @return \Elasticsearch\Client
     */
    public function getClient()
    {
        return $this->config['client'];
    }

    /**
     * @param       $term
     * @param array $options
     * @return \Iverberk\Larasearch\Response
     */
    public function search($term, $options = [])
    {
        return App::make('iverberk.larasearch.query', ['proxy' => $this, 'term' => $term, 'options' => $options])->execute();
    }

    /**
     * Performs a search based on a custom Elasticsearch query
     *
     * @param array $query
     * @param array $options
     * @return \Iverberk\Larasearch\Response
     */
    public function searchByQuery($query, $options = [])
    {
        $options = array_merge(['query' => $query], $options);

        return App::make('iverberk.larasearch.query', ['proxy' => $this, 'term' => null, 'options' => $options])->execute();
    }

    /**
     * Retrieves a single document by identifier
     *
     * @param $id
     * @return Result
     */
    public function searchById($id)
    {
        return App::make('iverberk.larasearch.response.result', $this->config['client']->get(
                [
                    'index' => $this->getIndex()->getName(),
                    'type' => $this->getType(),
                    'id' => $id
                ]
            )
        );
    }

    /**
     * @param bool $relations
     * @param int $batchSize
     * @param array $mapping
     * @param callable $callback
     * @internal param bool $force
     * @internal param array $params
     */
    public function reindex($relations = false, $batchSize = 750, $mapping = [], Callable $callback = null)
    {
        $model = $this->config['model'];
        $name = $this->config['index']->getName();

        $newName = $name . '_' . date("YmdHis");
        $relations = $relations ? Config::get('larasearch.paths.' . get_class($model)) : [];

        Index::clean($name);

        $index = App::make('iverberk.larasearch.index', array('name' => $newName, 'proxy' => $this));
        $index->create($mapping);

        if ($index->aliasExists($name))
        {
            $index->import($model, $relations, $batchSize, $callback);
            $remove = [];

            foreach (Index::getAlias($name) as $index => $aliases)
            {
                $remove = [
                    'remove' => [
                        'index' => $index,
                        'alias' => $name
                    ]
                ];
            }

            $add = [
                'add' => [
                    'index' => $newName,
                    'alias' => $name
                ]
            ];

            $actions[] = array_merge($remove, $add);

            Index::updateAliases(['actions' => $actions]);
            Index::clean($name);
        } else
        {
            if ($this->config['index']->exists()) $this->config['index']->delete();

            $actions[] =
                [
                    'add' => [
                        'index' => $newName,
                        'alias' => $name
                    ]
                ];

            Index::updateAliases([
                'actions' => $actions
            ]);

            $index->import($model, $relations, $batchSize, $callback);
        }

        Index::refresh($name);
    }

    /**
     * Determine if the model requires a (re)index. Defaults to 'true' but can
     * be overridden by user-defined logic.
     *
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
        $this->config['client']->index(
            [
                'id' => $model->getEsId(),
                'index' => $this->getIndex()->getName(),
                'type' => $this->getType(),
                'body' => $model->transform(true)
            ]
        );
    }

    /**
     * Delete a specific database record within Elasticsearch
     *
     * @param $id Eloquent id of model object
     */
    public function deleteDoc($id)
    {
        $this->config['client']->delete(
            [
                'id' => $id,
                'index' => $this->getIndex()->getName(),
                'type' => $this->getType()
            ]
        );
    }

    /**
     * Globally enable (re)indexing for this model
     */
    public function enableIndexing()
    {
        $class = get_class($this->config['model']);

        $class::$__es_enable = true;
    }

    /**
     * Globally disable (re)indexing for this model
     */
    public function disableIndexing()
    {
        $class = get_class($this->config['model']);

        $class::$__es_enable = false;
    }

}
