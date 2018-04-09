<?php namespace Iverberk\Larasearch\Traits;

use Illuminate\Support\Facades\Config;

trait TransformableTrait
{

    /**
     * Transform the Person model and its relations to an Elasticsearch document.
     *
     * @param bool $relations
     *
     * @return array
     */
    public function transform($relations = false)
    {
        $relations = $relations ? Config::get('larasearch.paths.' . get_class($this)) : [];

        // #TODO5 Not legit
        $this->isElasticSearch = true;

        $doc = $this->load($relations)->toArray();

        return $doc;
    }
}
