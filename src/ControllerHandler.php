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
use Modules\Annotation\Comment;

class ControllerHandler
{
    private $templateLoader;
    private $layoutMap = array();
    private $assignedVariables = array();
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
        }
        if ($controller instanceof Controller) {
            // Add templating related methods
            $controller->addMethods($this, array('assign', 'layout'));
        }
    }

    public function onControllerFinished($controller, $action, $returnValue)
    {
        if ($this->shouldNotRenderTemplate($controller, $returnValue)) {
            return;
        }
        if (!isset($this->currentLayout)) {
            if (!$this->determineCurrentLayout($controller, $action)) {
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
     * @param $returnValue
     *
     * @return bool
     */
    protected function shouldNotRenderTemplate($controller, $returnValue)
    {
        if ($controller instanceof Controller) {
            if ($controller->getHeaders()->has('location')) {
                return true;
            }
        }

        return $returnValue === false;
    }

    private function determineCurrentLayout($controller, $action)
    {
        if (isset($this->layoutMap[$action])) {
            $this->currentLayout = $this->layoutMap[$action];

            return true;
        }
        if (isset($this->annotation)) {
            $comment = $this->getControllerActionAnnotations($controller, $action);
            if ($comment->has('template')) {
                $this->currentLayout = $comment->get('template');

                return true;
            }

            if ($controller instanceof Controller) {
                $comment = $this->annotation->readClass($controller);
                if ($comment->has('templateDir')) {
                    $templateDir         = $comment->get('templateDir');
                    $this->currentLayout = rtrim($templateDir, '/') . '/' . $action;

                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param $controller
     * @param $action
     *
     * @return Comment
     */
    private function getControllerActionAnnotations($controller, $action)
    {
        if ($controller instanceof Closure) {
            return $this->annotation->readFunction($controller);
        } else {
            return $this->annotation->readMethod($controller, $action . 'Action');
        }
    }
}
