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
use Modules\Templating\Extensions\Debug;
use Modules\Templating\Extensions\Optimizer;
use Modules\Templating\Extensions\StandardFunctions;

class Module extends \Miny\Modules\Module
{

    public function defaultConfiguration()
    {
        return array(
            'options'                    => array(
                'global_variables' => array(),
                'cache_namespace'  => 'Application\\Templating\\Cached',
                'cache'            => 'templates/compiled',
                'autoescape'       => 1,
                'fallback_tag'     => 'print',
                'debug'            => $this->application->isDeveloperEnvironment()
            ),
            'template_loader'            => __NAMESPACE__ . '\\TemplateLoaders\\FileLoader',
            'template_loader_parameters' => array(
                '{@root}/templates',
                'tpl'
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
            $this->getConfiguration('template_loader')
        );
        $container->setConstructorArguments(
            $this->getConfiguration('template_loader'),
            $this->getConfiguration('template_loader_parameters')
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
        $env = new Environment(
            $container->get(__NAMESPACE__ . '\\AbstractTemplateLoader'),
            $this->getConfiguration('options')
        );

        //Environment is a dependency of TemplateLoader so this line is needed
        //to avoid infinite recursion
        $container->setInstance($env);

        $env->addExtension(new Core());
        $env->addExtension(new StandardFunctions());
        $env->addExtension(new Optimizer());
        $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Miny'));

        if ($env->getOption('debug', false)) {
            $env->addExtension(new Debug());

            if ($env->getOption('enable_node_tree_visualizer', false)) {
                $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Visualizer'));
            }
        }

        return $env;
    }

    private function setupAutoLoader(AutoLoader $autoLoader)
    {
        if ($this->getConfiguration('options:cache', false)) {
            $autoLoader->register(
                '\\' . $this->getConfiguration('options:cache_namespace'),
                $this->getConfiguration('options:cache')
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
