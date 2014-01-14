<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Miny\Application\BaseApplication;
use Miny\Factory\Factory;
use Miny\HTTP\Request;
use Miny\HTTP\Response;
use UnexpectedValueException;

class Module extends \Miny\Modules\Module
{

    public function defaultConfiguration()
    {
        return array(
            'templating' => array(
                'options' => array(
                    'reload'           => false,
                    'global_variables' => array(),
                    'cache_namespace'  => 'Application\\Templating\\Cached',
                    'strict_mode'      => true,
                    'reload'           => false,
                    'cache_path'       => 'templates/compiled/%s.php',
                    'template_path'    => 'templates/%s.tpl',
                    'autoescape'       => true
                ),
                'codes'   => array(),
            )
        );
    }

    public function init(BaseApplication $app)
    {
        $factory = $app->getFactory();

        $this->setupAutoloader($factory, $factory->getParameters());

        $factory->getBlueprint('events')
                ->addMethodCall('register', 'filter_response', array($this, 'handleResponseCodes'))
                ->addMethodCall('register', 'uncaught_exception', array($this, 'handleException'));

        $factory->add('miny_extensions', __NAMESPACE__ . '\\Extensions\\Miny')
                ->setArguments('&app');
        $factory->add('template_environment', __NAMESPACE__ . '\\Environment')
                ->setArguments('@templating:options')
                ->addMethodCall('addExtension', '&miny_extensions');
        $factory->add('template_plugins', __NAMESPACE__ . '\\Plugins')
                ->setArguments('&app');
        $factory->add('template_compiler', __NAMESPACE__ . '\\Compiler\\Compiler')
                ->setArguments('&template_environment');
        $factory->add('template_loader', __NAMESPACE__ . '\\TemplateLoader')
                ->setArguments('&template_environment', '&template_compiler', '&log');
        $factory->add('templating_controller', __NAMESPACE__ . '\\TemplateController')
                ->setParent('controller')
                ->addMethodCall('setTemplateLoader', '&template_loader');
    }

    private function setupAutoloader(Factory $factory, $options)
    {
        $dirname   = dirname($options['templating']['options']['cache_path']);
        if (!is_dir($dirname)) {
            mkdir($dirname);
        }
        $factory->get('autoloader')->register('\\' . $options['templating']['options']['cache_namespace'], $dirname);
    }

    public function handleResponseCodes(Request $request, Response $response)
    {
        $factory = $this->application->getFactory();
        $parameters = $factory->getParameters();
        $handlers   = $parameters['templating']['codes'];
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
            $loader   = $factory->get('template_loader');
            $template = $loader->load($template_name);
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
        $factory = $this->application->getFactory();
        $parameters = $factory->getParameters();
        if (!isset($parameters['templating']['exceptions'])) {
            return;
        }
        $loader   = $factory->get('template_loader');
        $handlers = $parameters['templating']['exceptions'];
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
