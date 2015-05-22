<?php namespace Iverberk\Larasearch\Traits;

use Illuminate\Support\Facades\Config;

trait TransformableTrait {

	/**
	 * Transform the Person model and its relations to an Elasticsearch document.
	 *
	 * @param bool $relations
	 * @return array
	 */
	public function transform($relations = false)
	{
		$relations = $relations ? Config::get('larasearch::paths.' . get_class($this)) : [];


		foreach ($this->getColunmTypes('Date') as $field => $type) {
			if(strpos(strtolower($this->{$field}),"0000")!==false)
			{
				$this->{$field}=null;
			}
		};
		$doc = $this->load($relations)->toArray();

		return $doc;
	}

	public function getColunmTypes($search = null)
	{
		$columns = [];
		$table_columns = \DB::getDoctrineSchemaManager()
					->listTableDetails(
						$this->getTable())
					->getColumns();
		if (!is_null($search)) {
			foreach ($table_columns as $key => $value) 
			{
				$name = preg_replace('/(".*Doctrine.*\\\\)(.*?)(Type:.*)/', "$2", $value->toArray()['type']);
				if(strpos(strtolower($name),strtolower($search))!== false)
					$columns[$key]=$name;
			}
		}
		else
		{
			foreach ($table_columns as $key => $value) 
			{
				$columns[$key]=preg_replace('/(".*Doctrine.*\\\\)(.*?)(Type:.*)/', "$2", $value->toArray()['type']);
			}	
		}
		return $columns;
	}
}