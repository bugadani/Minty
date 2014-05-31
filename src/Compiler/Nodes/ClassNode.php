<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Nodes;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Node;

class ClassNode extends Node
{
    private $templateName;
    private $parentTemplateName;

    public function __construct($templateName)
    {
        $this->templateName = $templateName;
    }

    public function hasParentTemplate()
    {
        return isset($this->parentTemplateName);
    }

    public function setParentTemplate($parentClass)
    {
        $this->parentTemplateName = $parentClass;
    }

    public function getParentTemplate()
    {
        return $this->parentTemplateName;
    }

    public function getTemplateName()
    {
        return $this->templateName;
    }

    public function getNameSpace()
    {
        $lastPos   = strrpos($this->templateName, '/');
        $namespace = 'Application\\Templating\\Cached';
        if ($lastPos !== false) {
            $directory = substr($this->templateName, 0, $lastPos);
            $namespace .= '\\' . strtr($directory, '/', '\\');
        }

        return $namespace;
    }

    public function addChild(Node $node, $key = null)
    {
        if (!$node instanceof RootNode) {
            throw new \InvalidArgumentException('ClassNode expects only RootNode children');
        }

        return parent::addChild($node, $key);
    }

    public function getClassName()
    {
        $path = strtr($this->templateName, '/', '\\');
        $pos  = strrpos('\\' . $path, '\\');

        return 'Template_' . substr($path, $pos);
    }

    public function getParentClassName()
    {
        if (!isset($this->parentTemplateName)) {
            return 'Modules\\Templating\\Template';
        }

        $path      = strtr($this->parentTemplateName, '/', '\\');
        $pos       = strrpos('\\' . $path, '\\');
        $className = substr($path, $pos);
        $namespace = substr($path, 0, $pos);

        return 'Application\\Templating\\Cached\\' . $namespace . 'Template_' . $className;

    }

    public function compile(Compiler $compiler)
    {
        //compile constructor
        $parentClass = $this->getParentClassName();
        $className   = $this->getClassName();
        $compiler->indented("class {$className} extends \\{$parentClass}");
        $compiler->indented('{');
        $compiler->indent();

        $this->compileConstructor($compiler);

        //if this is a template which extends an other, don't generate a render method
        if ($this->hasParentTemplate()) {
            $this->removeChild('render');
        }

        //compile methods
        foreach ($this->getChildren() as $method => $body) {
            /** @var $body RootNode */
            $this->compileMethod($compiler, $method, $body);
        }

        $compiler->outdent();
        $compiler->indented("}\n");
    }

    private function compileConstructor(Compiler $compiler)
    {
        $compiler->indented(
            'public function __construct(TemplateLoader $loader, Environment $environment)'
        );
        $compiler->indented('{');
        $compiler->indent();
        $compiler->indented('parent::__construct($loader, $environment);');
        if ($this->hasParentTemplate()) {
            $compiler->indented('$this->setParentTemplate("%s");', $this->parentTemplateName);
        }
        $compiler->outdent();
        $compiler->indented("}\n");
    }

    private function compileMethod(Compiler $compiler, $method, RootNode $body)
    {
        $compiler->indented('public function %s()', $method);
        $compiler->indented('{');
        $compiler->indent();
        $compiler->compileNode($body);
        $compiler->outdent();
        $compiler->indented("}\n");
    }
}
