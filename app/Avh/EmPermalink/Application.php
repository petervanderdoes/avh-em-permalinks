<?php
namespace Avh\EmPermalink;

use Avh\EmPermalink\Contracts\Foundation\ApplicationInterface;
use Avh\EmPermalink\Helpers\CommonHelper;
use Avh\EmPermalink\Support\ProviderRepository;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;

/**
 * Class Application
 *
 * @property Repository config
 * @package   Avh\EmPermalink
 * @author    Peter van der Does <peter@avirtualhome.com>
 * @copyright Copyright (c) 2015, AVH Software
 */
class Application extends Container implements ApplicationInterface
{
    /**
     * The Plugin Version
     *
     * @var string
     */
    const VERSION = '';
    /**
     * Indicates if the application has "booted".
     *
     * @var bool
     */
    protected $booted = false;
    /**
     * The array of booted callbacks.
     *
     * @var array
     */
    protected $bootedCallbacks = [];
    /**
     * The array of booting callbacks.
     *
     * @var array
     */
    protected $bootingCallbacks = [];
    /**
     * The deferred services and their providers.
     *
     * @var array
     */
    protected $deferredServices = [];
    /**
     * The environment file to load during bootstrapping.
     *
     * @var string
     */
    protected $environmentFile = '.env';
    /**
     * Indicates if the application has been bootstrapped before.
     *
     * @var bool
     */
    protected $hasBeenBootstrapped = false;
    /**
     * The names of the loaded service providers.
     *
     * @var array
     */
    protected $loadedProviders = [];
    /**
     * All of the registered service providers.
     *
     * @var array
     */
    protected $serviceProviders = [];
    /**
     * The custom storage path defined by the developer.
     *
     * @var string
     */
    protected $storagePath;
    /**
     * The array of terminating callbacks.
     *
     * @var array
     */
    protected $terminatingCallbacks = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->registerBaseBindings();
        $items = [];
        $this->instance('config', $config = new Repository($items));

        $this->config['app.providers'] = [];
        $this->registerCoreContainerAliases();
        $this->registerConfiguredProviders();
    }

    /**
     * Get the service providers that have been loaded.
     *
     * @return array
     */
    public function getLoadedProviders()
    {
        return $this->loadedProviders;
    }

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param  \Illuminate\Support\ServiceProvider|string $provider
     *
     * @return \Illuminate\Support\ServiceProvider|null
     */
    public function getProvider($provider)
    {
        $name = is_string($provider) ? $provider : get_class($provider);

        return array_first(
            $this->serviceProviders,
            function ($key, $value) use ($name) {
                return $value instanceof $name;
            }
        );
    }

    /**
     * Determine if the given service is a deferred service.
     *
     * @param  string $service
     *
     * @return bool
     */
    public function isDeferredService($service)
    {
        return isset($this->deferredServices[$service]);
    }

    /**
     * Load the provider for a deferred service.
     *
     * @param  string $service
     *
     * @return void
     */
    public function loadDeferredProvider($service)
    {
        if (!isset($this->deferredServices[$service])) {
            return;
        }

        $provider = $this->deferredServices[$service];

        // If the service provider has not already been loaded and registered we can
        // register it with the application and remove the service from this list
        // of deferred services, since it will already be loaded on subsequent.
        if (!isset($this->loadedProviders[$provider])) {
            $this->registerDeferredProvider($provider, $service);
        }
    }

    /**
     * Load and boot all of the remaining deferred providers.
     *
     * @return void
     */
    public function loadDeferredProviders()
    {
        // We will simply spin through each of the deferred providers and register each
        // one and boot them if the application has booted. This should make each of
        // the remaining services available to this application for immediate use.
        foreach ($this->deferredServices as $service => $provider) {
            $this->loadDeferredProvider($service);
        }

        $this->deferredServices = [];
    }

    /**
     * Resolve the given type from the container.
     * (Overriding Container::make)
     *
     * @param  string $abstract
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function make($abstract, $parameters = [])
    {
        $abstract = $this->getAlias($abstract);

        if (isset($this->deferredServices[$abstract])) {
            $this->loadDeferredProvider($abstract);
        }

        return parent::make($abstract, $parameters);
    }

    /**
     * Register a service provider with the application.
     *
     * @param  \Illuminate\Support\ServiceProvider|string $provider
     * @param array                                       $options
     * @param  bool                                       $force
     *
     * @return \Illuminate\Support\ServiceProvider
     */
    public function register($provider, $options = [], $force = false)
    {
        $registered = $this->getProvider($provider);
        if ($registered !== null && !$force) {
            return $registered;
        }

        // If the given "provider" is a string, we will resolve it, passing in the
        // application instance automatically for the developer. This is simply
        // a more convenient way of specifying your service provider classes.
        if (is_string($provider)) {
            $provider = $this->resolveProviderClass($provider);
        }

        $provider->register();

        return $provider;
    }

    /**
     * Register all of the configured providers.
     *
     * @return void
     */
    public function registerConfiguredProviders()
    {
        $upload_dir_info = wp_upload_dir();
        $manifestPath_directory = $upload_dir_info['basedir'] . '/avh-em-permalinks/services';
        CommonHelper::createDirectory($manifestPath_directory);
        $manifestPath = $manifestPath_directory . '/services.json';

        (new ProviderRepository($this, new Filesystem(), $manifestPath))->load($this->config['app.providers']);
    }

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    public function registerCoreContainerAliases()
    {
        $core_aliases = [
            'app'    => [
                'Illuminate\Foundation\Application',
                'Illuminate\Contracts\Container\Container',
                'Illuminate\Contracts\Foundation\Application'
            ],
            'config' => ['Illuminate\Config\Repository', 'Illuminate\Contracts\Config\Repository'],
        ];

        foreach ($core_aliases as $key => $aliases) {
            foreach ((array) $aliases as $alias) {
                $this->alias($key, $alias);
            }
        }
    }

    /**
     * Register a deferred provider and service.
     *
     * @param  string      $provider
     * @param  string|null $service
     *
     * @return void
     */
    public function registerDeferredProvider($provider, $service = null)
    {
        // Once the provider that provides the deferred service has been registered we
        // will remove it from our local list of the deferred services with related
        // providers so that this container does not try to resolve it out again.
        if ($service !== null) {
            unset($this->deferredServices[$service]);
        }

        $this->register($instance = new $provider($this));
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string $provider
     *
     * @return \Illuminate\Support\ServiceProvider
     */
    public function resolveProviderClass($provider)
    {
        return new $provider($this);
    }

    /**
     * Set the application's deferred services.
     *
     * @param  array $services
     *
     * @return void
     */
    public function setDeferredServices(array $services)
    {
        $this->deferredServices = $services;
    }

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version()
    {
        return '';
    }

    /**
     * Register the basic bindings into the container.
     *
     * @return void
     */
    protected function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);

        $this->instance('Illuminate\Container\Container', $this);
    }
}
