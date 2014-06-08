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

class Module extends \Miny\Modules\Module
{

    public function defaultConfiguration()
    {
        return array(
            'options' => array(
                'global_variables'    => array(),
                'cache_namespace'     => 'Application\\Templating\\Cached',
                'cache_path'          => 'templates/compiled/%s.php',
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
                $module->setupEnvironment($container);
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

        $env->addExtension(new Core());
        $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Optimizer'));
        $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Miny'));

        if ($env->getOption('debug', false)) {
            //Environment is a dependency of Debug extension so this line is needed
            //to avoid infinite recursion
            $container->setInstance($env);
            $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Debug'));

            if ($env->getOption('enable_node_tree_visualizer', false)) {
                $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Visualizer'));
            }
        }

        return $env;
    }

    private function setupAutoLoader(AutoLoader $autoLoader)
    {
        $cacheDirectoryName = dirname($this->getConfiguration('options:cache_path'));
        if (!is_dir($cacheDirectoryName)) {
            mkdir($cacheDirectoryName);
        }
        $autoLoader->register(
            '\\' . $this->getConfiguration('options:cache_namespace'),
            $cacheDirectoryName
        );
    }

    public function eventHandlers()
    {
        $container         = $this->application->getContainer();
        $controllerHandler = $container->get(
            __NAMESPACE__ . '\\EventHandlers',
            array(
                $this->getConfigurationTree()
            )
        );

        return $controllerHandler->getHandledEvents();
    }
}
