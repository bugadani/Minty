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
use Modules\Templating\Environment;

class ClassNode extends Node
{
    private $templateName;
    private $parentTemplateName;
    private $namespace;
    private $baseClass;

    public function __construct(Environment $env, $templateName)
    {
        $this->templateName = $templateName;
        $this->namespace    = $env->getOption('cache_namespace', 'Application\\Templating\\Cached');
        $this->baseClass    = $env->getOption(
            'template_base_class',
            'Modules\\Templating\\Template'
        );
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
        $lastPos    = strrpos($this->templateName, '/');
        $baseString = $this->namespace;

        if ($lastPos !== false) {
            $directory = substr($this->templateName, 0, $lastPos);
            $baseString .= '\\' . strtr($directory, '/', '\\');
        }

        return $baseString;
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
            return $this->baseClass;
        }

        $path      = strtr($this->parentTemplateName, '/', '\\');
        $pos       = strrpos('\\' . $path, '\\');
        $className = substr($path, $pos);
        $namespace = substr($path, 0, $pos);

        return "{$this->namespace}\\{$namespace}Template_{$className}";
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
