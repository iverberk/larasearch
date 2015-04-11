<?php namespace Iverberk\Larasearch\Traits;

use Illuminate\Support\Facades\App;

trait TransformableTrait {

	/**
	 * Transform the Person model and its relations to an Elasticsearch document.
	 *
	 * @param bool $relations
	 * @return array
	 */
	public function transform($relations = false)
	{
		$relations = $relations ? App::make('iverberk.larasearch.config')->get('paths.' . get_class($this)) : [];

		$doc = $this->load($relations)->toArray();

		return $doc;
	}

}