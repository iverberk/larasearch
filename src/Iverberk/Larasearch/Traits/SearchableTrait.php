<?php namespace Iverberk\Larasearch\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

trait SearchableTrait {

	use TransformableTrait;
	use CallableTrait;

	/**
	 * The Elasticsearch proxy class
	 *
	 * @var \Iverberk\Larasearch\Proxy
	 */
	protected static $__es_proxy = null;

	/**
	 * Related Eloquent models as dot seperated paths
	 *
	 * @var array
	 */
	private static $__es_paths = [];

	/**
	 * Return an instance of the Elasticsearch proxy
	 *
	 * @throws \Exception
	 * @return \Iverberk\Larasearch\Proxy | bool
	 */
	public static function getProxy()
	{
		if ( ! static::$__es_proxy)
		{
			$instance = new static;

			if ($instance instanceof Model)
			{
				static::$__es_proxy = App::make('iverberk.larasearch.proxy', $instance);

				return static::$__es_proxy;
			}
			else
			{
				throw new \Exception("This trait can ony be used in Eloquent models.");
			}
		}

		return static::$__es_proxy;
	}

	/**
	 * Clear the Elasticsearch proxy
	 */
	public static function clearProxy()
	{
		static::$__es_proxy = null;
	}

	/**
	 * Catch dynamic method calls intended for the Elasticsearch proxy
	 *
	 * @param  string $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		$proxy = static::getProxy();

		if (method_exists($proxy, $method))
		{
			return call_user_func_array(array($proxy, $method), $parameters);
		}

		return parent::__call($method, $parameters);
	}

	/**
	 * Catch dynamic static method calls intended for the Elasticsearch proxy
	 *
	 * @param  string $method
	 * @param  array  $parameters
	 * @return mixed
	 */
	public static function __callStatic($method, $parameters)
	{
		$proxy = static::getProxy();

		if (method_exists($proxy, $method))
		{
			return call_user_func_array(array($proxy, $method), $parameters);
		}

		return parent::__callStatic($method, $parameters);
	}

}
