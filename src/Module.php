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
use Modules\Templating\Extensions\Miny;
use UnexpectedValueException;

class Module extends \Miny\Modules\Module
{

    public function defaultConfiguration()
    {
        return array(
            'options' => array(
                'reload'           => false,
                'global_variables' => array(),
                'cache_namespace'  => 'Application\\Templating\\Cached',
                'strict_mode'      => true,
                'cache_path'       => 'templates/compiled/%s.php',
                'template_path'    => 'templates/%s.tpl',
                'autoescape'       => true,
                'delimiters'       => array(
                    'tag'     => array('{', '}'),
                    'comment' => array('{#', '#}')
                )
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

        $options = $this->getConfiguration('options');
        $container->addConstructorArguments(__NAMESPACE__ . '\\Environment', $options);
        $container->addCallback(
            __NAMESPACE__ . '\\Environment',
            function (Environment $environment, Container $container) {
                /** @var $minyExtension Miny */
                $minyExtension = $container->get(__NAMESPACE__ . '\\Extensions\\Miny');
                $environment->addExtension($minyExtension);
            }
        );

        $this->ifModule(
            'Annotation',
            function () use ($container) {
                $container->addCallback(
                    __NAMESPACE__ . '\\ControllerHandler',
                    function (ControllerHandler $handler, Container $container) {
                        /** @var $annotation \Modules\Annotation\Annotation */
                        $annotation = $container->get('\Modules\Annotation\Annotation');
                        $handler->setAnnotation($annotation);
                    }
                );
            }
        );
    }

    public function eventHandlers()
    {
        $container = $this->application->getContainer();
        /** @var $controllerHandler ControllerHandler */
        $controllerHandler = $container->get(__NAMESPACE__ . '\\ControllerHandler');

        $set_loader = function () use ($container, $controllerHandler) {
            /** @var $loader TemplateLoader */
            $loader = $container->get(__NAMESPACE__ . '\\TemplateLoader');
            $controllerHandler->setTemplateLoader($loader);
        };

        return array(
            'filter_response'      => array($this, 'handleResponseCodes'),
            'uncaught_exception'   => array($this, 'handleException'),
            'onControllerLoaded'   => array(
                $set_loader,
                $controllerHandler
            ),
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
        $container  = $this->application->getContainer();

        $handlers = $this->getConfiguration('codes');
        if (!is_array($handlers) || empty($handlers)) {
            return;
        }
        $response_code = $response->getCode();
        foreach ($handlers as $key => $handler) {
            if (!is_array($handler)) {
                $template_name = $handler;
                if (!$response->isCode($key)) {
                    continue;
                }
            } else {
                if (!isset($handler['codes'])) {
                    if (!isset($handler['code'])) {
                        throw new UnexpectedValueException('Response code handler must contain key "code" or "codes".');
                    }
                    if (!$response->isCode($handler['code'])) {
                        continue;
                    }
                } elseif (!in_array($response_code, $handler['codes'])) {
                    continue;
                }
                if (!isset($handler['template'])) {
                    throw new UnexpectedValueException('Response code handler must specify a template.');
                }
                $template_name = $handler['template'];
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
            break;
        }
    }

    public function handleException(\Exception $e)
    {
        $container  = $this->application->getContainer();

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
        }
        if (isset($template_name)) {
            $template = $loader->load($template_name);
            $template->set(
                array(
                    'exception' => $e
                )
            );
            $template->render();
        }
    }
}
