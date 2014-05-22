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
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use Modules\Templating\Extensions\Core;
use UnexpectedValueException;

class Module extends \Miny\Modules\Module
{

    public function defaultConfiguration()
    {
        return array(
            'options' => array(
                'global_variables' => array(),
                'cache_namespace'  => 'Application\\Templating\\Cached',
                'cache_path'       => 'templates/compiled/%s.php',
                'autoescape'       => true,
                'fallback_tag'     => 'print',
                'template_loader'  => __NAMESPACE__ . '\\TemplateLoaders\\FileLoader',
                'debug'            => $this->application->isDeveloperEnvironment()
            ),
            'codes'   => array()
        );
    }

    public function init(BaseApplication $app)
    {
        $container = $app->getContainer();

        /** @var $autoLoader AutoLoader */
        $autoLoader = $container->get('\\Miny\\AutoLoader');
        $this->setupAutoLoader($autoLoader);

        $module = $this;
        $container->addAlias(
            __NAMESPACE__ . '\\Environment',
            function (Container $container) use ($module) {
                $env = new Environment($module->getConfiguration('options'));

                $env->addExtension(new Core());
                $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Optimizer'));
                $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Miny'));

                if (!$env->getOption('debug', false)) {
                    return $env;
                }

                //Environment is a dependency of Debug extension so this line is needed
                //to avoid infinite recursion
                $container->setInstance($env);
                $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Debug'));

                if (!$env->getOption('enable_node_tree_visualizer', false)) {
                    return $env;
                }

                $env->addExtension($container->get(__NAMESPACE__ . '\\Extensions\\Visualizer'));

                return $env;
            }
        );

        $container->addAlias(
            __NAMESPACE__ . '\\AbstractTemplateLoader',
            $this->getConfiguration('options:template_loader')
        );
    }

    public function eventHandlers()
    {
        $container         = $this->application->getContainer();
        $controllerHandler = $container->get(__NAMESPACE__ . '\\ControllerHandler');

        return array(
            'filter_response'      => array($this, 'handleResponseCodes'),
            'uncaught_exception'   => array($this, 'handleException'),
            'onControllerLoaded'   => $controllerHandler,
            'onControllerFinished' => $controllerHandler
        );
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

    public function handleResponseCodes(Request $request, Response $response)
    {
        $container = $this->application->getContainer();

        $handlers = $this->getConfiguration('codes');
        if (!is_array($handlers) || empty($handlers)) {
            return;
        }
        $response_code = $response->getCode();
        foreach ($handlers as $key => $handler) {
            if (!is_array($handler)) {
                if (!$response->isCode($key)) {
                    continue;
                }
                $template_name = $handler;
            } else {
                if (isset($handler['codes'])) {
                    if (!in_array($response_code, $handler['codes'])) {
                        continue;
                    }
                } elseif (isset($handler['code'])) {
                    if (!$response->isCode($handler['code'])) {
                        continue;
                    }
                } else {
                    throw new UnexpectedValueException('Response code handler must contain key "code" or "codes".');
                }
                if (!isset($handler['template'])) {
                    throw new UnexpectedValueException('Response code handler must specify a template.');
                }
                $template_name = $handler['template'];
            }
            break;
        }
        if (!isset($template_name)) {
            return;
        }

        /** @var $loader TemplateLoader */
        $loader   = $container->get(__NAMESPACE__ . '\\TemplateLoader');
        $template = $loader->load($template_name);
        $template->set(
            array(
                'request'  => $request,
                'response' => $response
            )
        );
        $template->render();
    }

    public function handleException(\Exception $e)
    {
        $container = $this->application->getContainer();

        if (!$this->hasConfiguration('exceptions')) {
            return;
        }
        /** @var $loader TemplateLoader */
        $loader   = $container->get(__NAMESPACE__ . '\\TemplateLoader');
        $handlers = $this->getConfiguration('exceptions');
        if (!is_array($handlers)) {
            $template_name = $handlers;
        } else {
            foreach ($handlers as $class => $handler) {
                if ($e instanceof $class) {
                    $template_name = $handler;
                    break;
                }
            }
            if (!isset($template_name)) {
                return;
            }
        }
        $template = $loader->load($template_name);
        $template->set(array('exception' => $e));
        $template->render();
    }
}
