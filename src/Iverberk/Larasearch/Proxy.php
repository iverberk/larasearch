<?php namespace Iverberk\Larasearch;

use Illuminate\Database\Eloquent\Model;
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
        self::$config['type'] = str_singular($model->getTable());

        self::$config['client'] = App::make('Elasticsearch');
        self::$config['index'] = App::make('iverberk.larasearch.index', $this);
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
     * @internal param array $params
     */
    public static function reindex($force = false, $relations = false, $batchSize = 750, Callable $callback = null)
    {
        $index = self::$config['index'];
        $model = self::$config['model'];

        $relations = $relations ? Config::get('larasearch::paths.' . get_class($model)) : [];

        if ($force)
        {
            if ($index->exists()) $index->delete();
        }

        $index->import($model, $relations, $batchSize, $callback);
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
        self::$config['client']->index(
            [
                'id' => $model->id,
                'index' => $this->getIndex()->getName(),
                'type' => $this->getType(),
                'body' => $model->transform(true)
            ]
        );
    }

}