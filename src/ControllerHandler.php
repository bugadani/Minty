<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Closure;
use Miny\Controller\Controller;
use Modules\Annotation\Annotation;

class ControllerHandler
{
    private $templateLoader;
    private $layoutMap;
    private $assignedVariables;
    private $currentLayout;
    private $annotation;

    public function __construct(TemplateLoader $templateLoader, Annotation $annotation = null)
    {
        $this->annotation     = $annotation;
        $this->templateLoader = $templateLoader;
    }

    public function onControllerLoaded($controller)
    {
        if ($controller instanceof iTemplatingController) {
            $this->layoutMap = $controller->initLayouts();
        } else {
            $this->layoutMap = array();
        }
        $this->assignedVariables = array();
        // Add templating related methods
        $controller->addMethods($this, array('assign', 'layout'));
    }

    public function onControllerFinished($controller, $action, $controllerReturnValue)
    {
        if ($this->shouldNotRenderTemplate($controller, $controllerReturnValue)) {
            return;
        }
        if (!isset($this->currentLayout)) {
            if (isset($this->layoutMap[$action])) {
                $this->currentLayout = $this->layoutMap[$action];
            } elseif (isset($this->annotation)) {
                if ($controller instanceof Closure) {
                    $comment = $this->annotation->readFunction($controller);
                } else {
                    $comment = $this->annotation->readMethod($controller, $action . 'Action');
                }
                if ($comment->has('template')) {
                    $this->currentLayout = $comment->get('template');
                } else {
                    return;
                }
            } else {
                return;
            }
        }
        $layout = $this->templateLoader->load($this->currentLayout);
        $layout->set($this->assignedVariables);
        $layout->render();
    }

    public function layout($template)
    {
        $this->currentLayout = $template;
    }

    public function assign($key, $value)
    {
        $this->assignedVariables[$key] = $value;
    }

    /**
     * @param $controller
     * @param $controllerReturnValue
     *
     * @return bool
     */
    protected function shouldNotRenderTemplate($controller, $controllerReturnValue)
    {
        if($controller instanceof Controller && $controller->getHeaders()->has('location')) {
            return true;
        }
        return $controllerReturnValue === false;
    }
}
