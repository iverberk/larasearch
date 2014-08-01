<?php namespace Iverberk\Larasearch\Response;

use Illuminate\Support\Contracts\ArrayableInterface;

class Result implements \ArrayAccess, ArrayableInterface {

    /**
     * Contains an Elasticsearch hit response
     *
     * @access private
     * @var array
     */
    private $hit;

    /**
     * Construct the result with the an Elasticsearch hit array
     *
     * @access public
     * @param array $hit
     */
    public function __construct(array $hit)
    {
        $this->hit = $hit;
    }

    /**
     * Return the hit id
     *
     * @acccess public
     * @return integer
     */
    public function getId()
    {
        return (int) $this->hit['_id'];
    }

    /**
     * Return the hit document type
     *
     * @access public
     * @return string
     */
    public function getType()
    {
        return $this->hit['_type'];
    }

    /**
     * Return the hit index
     *
     * @access public
     * @return string
     */
    public function getIndex()
    {
        return $this->hit['_index'];
    }

    /**
     * Return the hit score
     *
     * @access public
     * @return float
     */
    public function getScore()
    {
        return (float) $this->hit['_score'];
    }

    /**
     * Return the _source object
     *
     * @access public
     * @return array
     */
    public function getSource()
    {
        return $this->hit['_source'];
    }

    /**
     * @param array $fields
     * @return array
     */
    public function getFields($fields = [])
    {
        $results = [];
        foreach($fields as $field)
        {
            $results[$field] = $this->hit['fields'][$field];
        }

        return empty($fields) ? $this->hit['fields'] : $results;
    }

    /**
     * Return the hit object
     *
     * @access public
     * @return array
     */
    public function getHit()
    {
        return $this->hit;
    }

    /**
     * @param array $fields
     * @return array
     */
    public function getHighlights($fields = [])
    {
        if (!empty($fields))
        {
            $results = [];
            foreach($fields as $field)
            {
                foreach($this->hit['highlight'] as $key => $value)
                {
                    if (preg_match("/^${field}.*/", $key) !== false)
                    {
                        $results[$field] = $value;
                    }
                }
            }
            return $results;
        }
        else
        {
            return $this->hit['highlight'];
        }
    }

    /**
     * Get data by key
     *
     * @param string The key data to retrieve
     * @return mixed
     * @access public
     */
    public function __get ($key)
    {
        $item = array_get($this->hit, $this->getPath($key));

        return $item;
    }

    /**
     * Whether or not an offset exists
     *
     * @param mixed $offset
     * @access public
     * @return boolean
     * @abstracting ArrayAccess
     */
    public function offsetExists($offset)
    {
        return (array_get($this->hit, $this->getPath($offset)) !== null);
    }

    /**
     * Returns the value at specified offset
     *
     * @param mixed $offset
     * @access public
     * @return mixed
     * @abstracting ArrayAccess
     */
    public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? array_get($this->hit, $this->getPath($offset)) : null;
    }

    /**
     * Assigns a value to the specified offset
     *
     * @param mixed $offset
     * @param mixed $value
     * @access public
     * @abstracting ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        // Not allowed for Elasticsearch responses, update the Eloquent model instead.
    }

    /**
     * Unsets an offset
     *
     * @param mixed $offset
     * @access public
     * @abstracting ArrayAccess
     */
    public function offsetUnset($offset)
    {
        // Not allowed for Elasticsearch responses, update the Eloquent model instead.
    }

    /**
     * Check if the $offset parameter contains a dot and return the appropriate path
     * in the array
     *
     * @access private
     * @param $offset
     * @return string
     */
    private function getPath($offset)
    {
        return (strpos($offset, '.') !== false) ? $path = $offset : "_source.${offset}";
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->hit['_source'];
    }

}