<?php
// Here you can initialize variables that will be available to your tests

require '../../../vendor/autoload.php';

use Elasticsearch\Client;
use Illuminate\Database\Capsule\Manager as Capsule;
use Iverberk\Larasearch\Query;
use Iverberk\Larasearch\Index;
use Iverberk\Larasearch\Traits\TransformableTrait;
use Iverberk\Larasearch\Traits\SearchableTrait;

// Setup Mocks for Laravel specific components

$app = new Illuminate\Container\Container;

$app->bind('config', function($app) {

    // Configurations
    $configPath = __DIR__.'/../../src/config';
    $environment = 'production';

    $file = new Illuminate\Filesystem\Filesystem;
    $loader = new Illuminate\Config\FileLoader($file, $configPath);

    $configMock = Mockery::mock('Illuminate\Config\Repository', array($loader, $environment));

    $configMock->shouldReceive('get')->withAnyArgs()->andReturnUsing(function($path) {

        $config = require __DIR__ . '/../../src/config/config.php';

        switch($path)
        {
            case 'larasearch::elasticsearch.analyzers':
                return $config['elasticsearch']['analyzers'];
                break;
            case 'larasearch::elasticsearch.defaults.index':
                return $config['elasticsearch']['defaults']['index'];
                break;
        }
    });

    return $configMock;

});

$app->singleton('app', function ($app) {

    $appMock = Mockery::mock('Illuminate\Container\Container');
    $configMock = Mockery::mock('Illuminate\Config\Repository');

    $configMock->shouldReceive('get')->withAnyArgs();

    $config = require __DIR__ . '/../../src/config/config.php';

    $appMock->shouldReceive('make')->withArgs(array('Elasticsearch'))->andReturn(
        new Client($config['elasticsearch']['params'])
    );

    $appMock->shouldReceive('make')->with('iverberk.larasearch.index', Mockery::type('string') )->andReturnUsing(function($class, $name) {
        return new Index($name);
    });

    $appMock->shouldReceive('make')->with('iverberk.larasearch.proxy', Mockery::type('Illuminate\Database\Eloquent\Model'))->andReturnUsing(function($class, $model) {
        $proxyMock = Mockery::mock('Iverberk\Larasearch\Proxy', array($model))->makePartial();

        $proxyMock->shouldReceive('getConfig')->andReturn(true);

        return $proxyMock;
    });

    $appMock->shouldReceive('make')->with('iverberk.larasearch.query', Mockery::type('array'))->andReturnUsing(function($class, $params) {
        return new Query($params['proxy'], $params['term'], $params['options']);
    });

    $appMock->shouldReceive('make')->withArgs(array('Config'))->andReturn(
        $configMock
    );

    return $appMock;

});

Illuminate\Support\Facades\Facade::setFacadeApplication($app);

// Boot the Eloquent component

$capsule = new Capsule;

$capsule->addConnection(array(
    'driver'   => 'sqlite',
    'database' => __DIR__.'/../_data/testing.sqlite',
    'prefix'   => '',
));

$capsule->bootEloquent();

// Setup some models

class Husband extends Illuminate\Database\Eloquent\Model {

    use SearchableTrait;

    public $__es_config = [
        'autocomplete' => ['name', 'wife.name'],

        'suggest' => ['name'],

        'text_start' => ['name', 'wife.children.name'],
        'text_middle' => ['name', 'wife.children.name'],
        'text_end' => ['name', 'wife.children.name'],

        'word_start' => ['name', 'wife.children.name'],
        'word_middle' => ['name', 'wife.children.name'],
        'word_end' => ['name', 'wife.children.name']
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function wife()
    {
        return $this->hasOne('Wife');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function children()
    {
        return $this->hasMany('Child', 'father_id');
    }

}

class Wife extends Illuminate\Database\Eloquent\Model {

    use TransformableTrait;

    /**
     * @follow NEVER
     *
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function husband()
    {
        return $this->belongsTo('Husband');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function children()
    {
        return $this->hasMany('Child', 'mother_id');
    }

}

class Child extends Illuminate\Database\Eloquent\Model {

    use TransformableTrait;

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function mother()
    {
        return $this->belongsTo('Wife');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function father()
    {
        return $this->belongsTo('Husband');
    }

    /**
     * @follow UNLESS Wife
     *
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function toys()
    {
        return $this->belongsToMany('Toy');
    }

}

class Toy extends Illuminate\Database\Eloquent\Model {

    use TransformableTrait;

    /**
     * @return \Illuminate\Database\Eloquent\Relations
     */
    public function children()
    {
        return $this->belongsToMany('Child');
    }

}