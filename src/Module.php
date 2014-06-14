<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Miny\Application\BaseApplication;
use Miny\AutoLoader;
use Miny\Factory\Container;
use Modules\Templating\Extensions\Core;
use Modules\Templating\Extensions\StandardFunctions;

class Module extends \Miny\Modules\Module
{

    public function defaultConfiguration()
    {
        return array(
            'options' => array(
                'global_variables'    => array(),
                'cache_namespace'     => 'Application\\Templating\\Cached',
                'cache'               => 'templates/compiled',
                'template_base_class' => 'Modules\\Templating\\Template',
                'autoescape'          => true,
                'fallback_tag'        => 'print',
                'template_loader'     => __NAMESPACE__ . '\\TemplateLoaders\\FileLoader',
                'debug'               => $this->application->isDeveloperEnvironment()
            )
        );
    }

    public function init(BaseApplication $app)
    {
        $container = $app->getContainer();

        $this->setupAutoLoader(
            $container->get('\\Miny\\AutoLoader')
        );

        $module = $this;
        $container->addAlias(
            __NAMESPACE__ . '\\Environment',
            function (Container $container) use ($module) {
                return $module->setupEnvironment($container);
            }
        );
        $container->addAlias(
            __NAMESPACE__ . '\\AbstractTemplateLoader',
            $this->getConfiguration('options:template_loader')
        );
    }

    /**
     * This method is responsible for initializing the Environment. Called by Container.
     *
     * @param Container $container
     *
     * @return Environment
     */
    public function setupEnvironment(Container $container)
    {
        $env = new Environment($this->getConfiguration('options'));
        //Environment is a dependency of TemplateLoader so this line is needed
        //to avoid infinite recursion
        $container->setInstance($env);
        $env->setTemplateLoader($container->get(__NAMESPACE__ . '\\TemplateLoader'));

        $env->addExtension(new Core());
        $env->addExtension(new StandardFunctions());
        $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Optimizer'));
        $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Miny'));

        if ($env->getOption('debug', false)) {
            $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Debug'));

            if ($env->getOption('enable_node_tree_visualizer', false)) {
                $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Visualizer'));
            }
        }

        return $env;
    }

    private function setupAutoLoader(AutoLoader $autoLoader)
    {
        if ($this->getConfiguration('options:cache', false)) {
            $cacheDirectoryName = dirname($this->getConfiguration('options:cache'));
            if (!is_dir($cacheDirectoryName)) {
                mkdir($cacheDirectoryName);
            }
            $autoLoader->register(
                '\\' . $this->getConfiguration('options:cache_namespace'),
                $cacheDirectoryName
            );
        }
    }

    public function eventHandlers()
    {
        $container         = $this->application->getContainer();
        $controllerHandler = $container->get(
            __NAMESPACE__ . '\\EventHandlers',
            array($this->getConfigurationTree())
        );

        return $controllerHandler->getHandledEvents();
    }
}
