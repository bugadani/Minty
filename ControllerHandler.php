<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

class ControllerHandler
{
    private $template_loader;
    private $layout_map;
    private $assigned_variables;
    private $current_layout;

    public function setTemplateLoader(TemplateLoader $loader)
    {
        $this->template_loader = $loader;
    }

    public function onControllerLoaded($controller, $action)
    {
        if (!$controller instanceof iTemplatingController) {
            return;
        }
        $this->assigned_variables = array();
        $this->layout_map         = $controller->initLayouts();
        // Add templating related methods
        $controller->addMethods($this, array('assign', 'layout'));
    }

    public function onControllerFinished($controller, $action, $controller_retval)
    {
        if (!$controller instanceof iTemplatingController) {
            return;
        }
        if ($controller->getHeaders()->has('location') || $controller_retval === false) {
            return;
        }
        if (!isset($this->current_layout)) {
            if (isset($this->layout_map[$action])) {
                $this->current_layout = $this->layout_map[$action];
            } else {
                return;
            }
        }
        $layout = $this->template_loader->load($this->current_layout);
        $layout->set($this->assigned_variables);
        $layout->render();
    }

    public function layout($template)
    {
        $this->current_layout = $template;
    }

    public function assign($key, $value)
    {
        $this->assigned_variables[$key] = $value;
    }
}
