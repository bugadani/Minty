<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating;

use Modules\Templating\Compiler\Environment;
use Modules\Templating\Compiler\Operator;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\TemplateFunction;

abstract class Extension
{
    private $extension_name;

    public function __construct()
    {
        $this->extension_name = $this->getExtensionName();
    }

    abstract public function getExtensionName();

    public function registerExtension(Environment $descriptor)
    {
        foreach ($this->getFunctions() as $function) {
            $function->setExtensionName($this->extension_name);
            $descriptor->addFunction($function);
        }
        foreach ($this->getOperators() as $operator) {
            $descriptor->addOperator($operator);
        }
        foreach ($this->getTags() as $tag) {
            $descriptor->addTag($tag);
        }
    }

    /**
     * @return TemplateFunction[]
     */
    public function getFunctions()
    {
        return array();
    }

    /**
     * @return Operator[]
     */
    public function getOperators()
    {
        return array();
    }

    /**
     * @return Tag[]
     */
    public function getTags()
    {
        return array();
    }
}
