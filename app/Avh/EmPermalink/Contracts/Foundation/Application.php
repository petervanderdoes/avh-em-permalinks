<?php
namespace Avh\EmPermalink\Contracts\Foundation;

/**
 * Interface ApplicationInterface
 *
 * @package Avh\EmPermalink\Contracts\Foundation
 */
interface ApplicationInterface
{
    /**
     * Get the service providers that have been loaded.
     *
     * @return array
     */
    public function getLoadedProviders();

    /**
     * Get the registered service provider instance if it exists.
     *
     * @param  \Illuminate\Support\ServiceProvider|string $provider
     *
     * @return \Illuminate\Support\ServiceProvider|null
     */
    public function getProvider($provider);

    /**
     * Determine if the given service is a deferred service.
     *
     * @param  string $service
     *
     * @return bool
     */
    public function isDeferredService($service);

    /**
     * Load the provider for a deferred service.
     *
     * @param  string $service
     *
     * @return void
     */
    public function loadDeferredProvider($service);

    /**
     * Load and boot all of the remaining deferred providers.
     *
     * @return void
     */
    public function loadDeferredProviders();

    /**
     * Resolve the given type from the container.
     * (Overriding Container::make)
     *
     * @param  string $abstract
     * @param  array  $parameters
     *
     * @return mixed
     */
    public function make($abstract, $parameters = []);

    /**
     * Register a service provider with the application.
     *
     * @param  \Illuminate\Support\ServiceProvider|string $provider
     * @param array                                       $options
     * @param  bool                                       $force
     *
     * @return \Illuminate\Support\ServiceProvider
     */
    public function register($provider, $options = [], $force = false);

    /**
     * Register all of the configured providers.
     */
    public function registerConfiguredProviders();

    /**
     * Register the core class aliases in the container.
     *
     * @return void
     */
    public function registerCoreContainerAliases();

    /**
     * Register a deferred provider and service.
     *
     * @param  string      $provider
     * @param  string|null $service
     *
     * @return void
     */
    public function registerDeferredProvider($provider, $service = null);

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string $provider
     *
     * @return \Illuminate\Support\ServiceProvider
     */
    public function resolveProviderClass($provider);

    /**
     * Set the application's deferred services.
     *
     * @param  array $services
     *
     * @return void
     */
    public function setDeferredServices(array $services);

    /**
     * Get the version number of the application.
     *
     * @return string
     */
    public function version();
}
