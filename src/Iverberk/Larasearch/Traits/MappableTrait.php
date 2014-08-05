<?php namespace Iverberk\Larasearch\Traits;

use Illuminate\Support\Facades\Config;
use Iverberk\Larasearch\Introspect;

trait MappableTrait {

    /**
     * Contains table column names and types
     *
     * @var array
     */
    protected static $columnTypes = [];

    protected $index = null;

    protected $type = null;

    /**
     * Boot the trait by registering column types per database table
     */
    public static function bootMappableTrait()
    {
        $instance = new static;

        if ( ! array_key_exists($instance->getTable(), static::$columnTypes))
        {
            static::$columnTypes[$instance->getTable()] = $instance->getProperties();
        }
    }

    /**
     * Create an Elasticsearch mapping for the properties of the model and its relations
     *
     * @access public
     * @return Array
     */
    public function getProperties()
    {
        $columns = with(new Introspect($this))->getColumns();
        $properties = [];

        foreach($columns as $column)
        {
            $name = $column->getName();

            switch ($type = $column->getType()->getName())
            {
                case 'text':
                    $properties[$name] = 'string';
                    break;
                case 'datetime':
                    $properties[$name] = 'date';
                    break;
                default:
                    $properties[$name] = $type;
            }
        }

        return $properties;
    }

    /**
     * Convert numeric string values returned by the mysql driver
     * to their proper numeric form.
     *
     * @access public
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->getAttributes();

        foreach($attributes as $key => $value)
        {
            if (self::$columnTypes[$this->getTable()][$key] == 'integer')
            {
                $attributes[$key] = (int) $attributes[$key];
            }
        }

        $this->setRawAttributes($attributes);

        return parent::toArray();
    }


    /**
     * Transform the Person model and its relations to an Elasticsearch document.
     *
     * @param bool $relations
     * @return array
     */
    public function transform($relations = false)
    {
        $relations = $relations ? Config::get('documents.' . get_class($this)) : [];
        $doc = $this->load($relations)->toArray();

        return $doc;
    }

}