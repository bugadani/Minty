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
                ->addMethodCall('register', 'filter_response', array($this, 'handleResponseCodes'));
        
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
        foreach ($handlers as $handler) {
            if (!isset($handler['codes'])) {
                if (!isset($handler['code'])) {
                    throw new UnexpectedValueException('Response code handler must contain key "code" or "codes".');
                }
                $codes = array($handler['code']);
            } else {
                $codes = array($handler['codes']);
            }
            if (!in_array($response_code, $codes)) {
                continue;
            }
            if (!isset($handler['template'])) {
                throw new UnexpectedValueException('Response code handler must specify a template.');
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
}
