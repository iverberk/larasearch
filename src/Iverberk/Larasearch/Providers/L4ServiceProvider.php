<?php namespace Iverberk\Larasearch\Providers;

class L4ServiceProvider extends ServiceProvider {

    public function boot()
    {
        $this->package('iverberk/larasearch');

        parent::boot();
    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->registerCommands();
	}
}
