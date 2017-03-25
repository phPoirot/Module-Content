<?php
namespace Module\Content;

use Poirot\Application\Interfaces\Sapi;
use Poirot\Application\ModuleManager\Interfaces\iModuleManager;
use Poirot\Ioc\Container;
use Poirot\Ioc\Container\BuildContainer;
use Poirot\Router\BuildRouterStack;
use Poirot\Router\Interfaces\iRouterStack;
use Poirot\Std\Interfaces\Struct\iDataEntity;


class Module implements Sapi\iSapiModule
    , Sapi\Module\Feature\iFeatureModuleInitModuleManager
    , Sapi\Module\Feature\iFeatureModuleMergeConfig
    , Sapi\Module\Feature\iFeatureModuleNestServices
    , Sapi\Module\Feature\iFeatureOnPostLoadModulesGrabServices
{
    const CONF_KEY = 'module.content';


    /**
     * Initialize Module Manager
     *
     * priority: 1000 C
     *
     * @param iModuleManager $moduleManager
     *
     * @return void
     */
    function initModuleManager(iModuleManager $moduleManager)
    {
        // ( ! ) ORDER IS MANDATORY

        if (!$moduleManager->hasLoaded('MongoDriver'))
            // MongoDriver Module Is Required.
            $moduleManager->loadModule('MongoDriver');
    }

    /**
     * Register config key/value
     *
     * priority: 1000 D
     *
     * - you may return an array or Traversable
     *   that would be merge with config current data
     *
     * @param iDataEntity $config
     *
     * @return array|\Traversable
     */
    function initConfig(iDataEntity $config)
    {
        return \Poirot\Config\load(__DIR__ . '/../../config/mod-content');
    }

    /**
     * Get Nested Module Services
     *
     * it can be used to manipulate other registered services by modules
     * with passed Container instance as argument.
     *
     * priority not that serious
     *
     * @param Container $moduleContainer
     *
     * @return null|array|BuildContainer|\Traversable
     */
    function getServices(Container $moduleContainer = null)
    {
        $conf = \Poirot\Config\load(__DIR__ . '/../../config/mod-content.services');
        return $conf;
    }

    /**
     * Resolve to service with name
     *
     * - each argument represent requested service by registered name
     *   if service not available default argument value remains
     * - "services" as argument will retrieve services container itself.
     *
     * ! after all modules loaded
     *
     * @param iRouterStack $router
     */
    function resolveRegisteredServices(
        $router = null
    ) {
        # Register Http Routes:
        if ($router) {
            $routes = include __DIR__ . '/../../config/mod-content.routes.conf.php';
            $buildRoute = new BuildRouterStack;
            $buildRoute->setRoutes($routes);
            $buildRoute->build($router);
        }
    }
}