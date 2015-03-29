<?php namespace Iverberk\Larasearch\Providers;

class L5ServiceProvider extends ServiceProvider {

    public function boot()
    {
        parent::boot();

        $this->publishes([
            __DIR__ . '/../../../onfig/config.php' => config_path('larasearch.php'),
        ], 'config');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerCommands();

        $this->mergeConfigFrom(
            __DIR__ . '/../../../config/config.php', 'larasearch'
        );
    }
}
