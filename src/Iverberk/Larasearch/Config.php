<?php namespace Iverberk\Larasearch;

use Illuminate\Contracts\Config\Repository;

class Config {

    /**
     * Laravel's configuation repository
     *
     * @var \Illuminate\Contracts\Config\Repository
     */
    protected $config;

    /**
     * Root configuration namespace
     *
     * @var string
     */
    protected $packageName;

    /**
     * String delimiter between package name and config setting key.
     *
     * @var string
     */
    protected $delimiter = '.';

    public function __construct(Repository $config, $packageName = 'larasearch')
    {
        $this->config = $config;
        $this->setPackageName($packageName);

        if (version_compare(constant('Illuminate\\Foundation\\Application::VERSION'), '5.0.0', '<')) $this->delimiter = '::';
    }

    public function setPackageName($packageName)
    {
        if ($packageName)
        {
            $this->packageName = (string)$packageName;
        }
    }

    public function getPackageName()
    {
        return $this->packageName;
    }

    public function get($name, $default = null)
    {
        return $this->config->get("{$this->packageName}{$this->delimiter}{$name}", $default);
    }

    public function set($name, $value = null)
    {
       $this->config->set("{$this->packageName}{$this->delimiter}{$name}", $value);
    }

}
