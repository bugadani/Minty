<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Miny\Application\BaseApplication;

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

        $app->add('template_descriptor', __NAMESPACE__ . '\\Compiler\\TemplateDescriptor');
        $app->add('template_plugins', __NAMESPACE__ . '\\Plugins')
                ->setArguments('&app');
        $app->add('template_compiler', __NAMESPACE__ . '\\Compiler\\TemplateCompiler')
                ->setArguments($options, '&template_descriptor');
        $app->add('template_loader', __NAMESPACE__ . '\\TemplateLoader')
                ->setArguments($options, '&template_compiler', '&template_plugins', '&log');
    }
}
