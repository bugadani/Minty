<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Miny\Controller\Controller as ControllerBase;
use Miny\HTTP\Request;
use Miny\HTTP\Response;

class Controller extends ControllerBase
{
    private $layout_map;
    private $current_layout;
    private $template_variables = array();
    private $template_loader;

    public function setTemplateLoader(TemplateLoader $loader)
    {
        $this->template_loader = $loader;
    }

    public function getTemplateLoader()
    {
        return $this->template_loader;
    }

    protected function initLayouts()
    {
        //need to be overridden
        //[action => template]
        return array();
    }

    protected function init()
    {
        $this->layout_map = $this->initLayouts();
    }

    public function layout($template)
    {
        //TODO: check
        $this->current_layout = $template;
    }

    public function assign($key, $value)
    {
        $this->template_variables[$key] = $value;
    }

    public function clean()
    {
        $this->template_variables = array();
    }

    protected function renderLayout()
    {
        if (isset($this->current_layout)) {
            $layout = $this->template_loader->load($this->current_layout);
            $layout->set($this->template_variables);
            echo $layout->render();
        }
    }

    protected function actionHasLayout($action)
    {
        return isset($this->layout_map[$action]);
    }

    protected function getLayoutForAction($action)
    {
        return $this->layout_map[$action];
    }

    public function run($action, Request $request, Response $response)
    {
        $action = $action ? : $this->default_action;
        if ($this->actionHasLayout($action)) {
            $this->layout($this->getLayoutForAction($action));
        }
        if (parent::run($action, $request, $response) !== false) {
            $this->renderLayout();
        }
    }
}
