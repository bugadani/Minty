<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Miny\Application\BaseApplication;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use UnexpectedValueException;

class Module extends \Miny\Application\Module
{

    public function init(BaseApplication $app)
    {
        if (isset($app['templating:options'])) {
            $options = new TemplatingOptions($app['templating']['options']);
        } else {
            $options = new TemplatingOptions();
        }
        $app->templating_options = $options;
        $app->autoloader->register('\\' . $options->cache_namespace, dirname($options->cache_path));

        $app->getBlueprint('events')
                ->addMethodCall('register', 'filter_response', array($this, 'handleResponseCodes'))
                ->addMethodCall('register', 'uncaught_exception', array($this, 'handleException'));

        $app->add('miny_extensions', __NAMESPACE__ . '\\Compiler\\Extensions\\Miny')
                ->setArguments('&app');
        $app->add('template_environment', __NAMESPACE__ . '\\Compiler\\Environment')
                ->setArguments('&templating_options')
                ->addMethodCall('addExtension', '&miny_extensions');
        $app->add('template_plugins', __NAMESPACE__ . '\\Plugins')
                ->setArguments('&app');
        $app->add('template_compiler', __NAMESPACE__ . '\\Compiler\\Compiler')
                ->setArguments('&template_environment');
        $app->add('template_loader', __NAMESPACE__ . '\\TemplateLoader')
                ->setArguments('&template_environment', '&template_compiler', '&log');
    }

    public function handleResponseCodes(Request $request, Response $response)
    {
        if (!isset($this->application['templating:codes'])) {
            return;
        }
        $handlers = $this->application['templating']['codes'];
        if (!is_array($handlers)) {
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
                } else {
                    if (!in_array($response_code, $handler['codes'])) {
                        continue;
                    }
                }
                if (!isset($handler['template'])) {
                    throw new UnexpectedValueException('Response code handler must specify a template.');
                }
                $template_name = $handler['template'];
            }
            $loader   = $this->application->template_loader;
            $template = $loader->load($handler['template']);
            $template->set(array(
                'request'  => $request,
                'response' => $response
            ));
            $template->render();
            break;
        }
    }

    public function handleException(\Exception $e)
    {
        if (!isset($this->application['templating:exceptions'])) {
            return;
        }
        $loader   = $this->application->template_loader;
        $handlers = $this->application['templating']['exceptions'];
        if (!is_array($handlers)) {
            $template_name = $handlers;
        } else {
            foreach ($handlers as $class => $handler) {
                if (!$e instanceof $class) {
                    continue;
                }
                $template_name = $handler;
                break;
            }
        }
        if (isset($template_name)) {
            $template = $loader->load($template_name);
            $template->set(array(
                'exception' => $e
            ));
            $template->render();
        }
    }
}
