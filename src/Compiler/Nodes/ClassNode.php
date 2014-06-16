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
    private $className;
    private $parentTemplateName;
    private $baseClass;

    public function __construct(Environment $env, $templateName)
    {
        $this->templateName = $templateName;
        $this->baseClass    = $env->getOption(
            'template_base_class',
            'Modules\\Templating\\Template'
        );
        $this->addData('self_accessed', true);

        $className = $env->getTemplateClassName($templateName);

        $classNameOffset = strrpos($className, '\\');
        $this->namespace = substr($className, 0, $classNameOffset);
        $this->className = substr($className, $classNameOffset + 1);
    }

    public function setParentTemplate(Node $parentClass)
    {
        $this->parentTemplateName = $parentClass;
    }

    public function getNameSpace()
    {
        return $this->namespace;
    }

    public function getClassName()
    {
        return $this->className;
    }

    public function addChild(Node $node, $key = null)
    {
        if (!$node instanceof RootNode) {
            throw new \InvalidArgumentException('ClassNode expects only RootNode children');
        }

        return parent::addChild($node, $key);
    }

    public function compile(Compiler $compiler)
    {
        //this is needed to convince PHPStorm that compileBlock receives a RootNode
        /** @var $body RootNode */

        $compiler->indented("class {$this->className} extends \\{$this->baseClass}");
        $compiler->indented('{');
        $compiler->indent();

        $this->compileConstructor($compiler);

        //if this is a template which extends an other, don't generate code for the default block
        if (!isset($this->parentTemplateName)) {

            //compile the main block method
            $body = $this->getChild('__main_template_block');
            $this->compileBlock($compiler, 'displayTemplate', $body);

        } elseif (!$this->parentTemplateName instanceof DataNode) {

            //compile a default displayTemplate that sets the parent template
            $compiler->indented('public function displayTemplate(Context $context)');
            $compiler->indented('{');
            $compiler->indent();
            $compiler->indented('$this->setParentTemplate(')
                ->compileNode($this->parentTemplateName)
                ->add(');');
            $compiler->indented('parent::displayTemplate($context);');
            $compiler->outdent();
            $compiler->indented("}\n");
        }
        $this->removeChild('__main_template_block');

        //compile blocks
        foreach ($this->getChildren() as $method => $body) {
            $this->compileBlock($compiler, 'block_' . $method, $body);
        }

        $compiler->outdent();
        $compiler->indented("}\n");
    }

    private function compileConstructor(Compiler $compiler)
    {
        $compiler->indented('public function __construct(Environment $environment)');
        $compiler->indented('{');
        $compiler->indent();

        $compiler->indented('$blocks = ')
            ->compileArray(array_keys($this->getChildren()), false)
            ->add(';');

        $compiler
            ->indented('parent::__construct($environment, ')
            ->compileString($this->templateName)
            ->add(', $blocks);');

        if (isset($this->parentTemplateName) && $this->parentTemplateName instanceof DataNode) {
            $compiler->indented('$this->setParentTemplate(')
                ->compileNode($this->parentTemplateName)
                ->add(');');
        }
        $compiler->outdent();
        $compiler->indented("}\n");
    }

    private function compileBlock(Compiler $compiler, $method, RootNode $body)
    {
        $compiler->indented('public function %s(Context $context)', $method);
        $compiler->indented('{');
        $compiler->indent();

        if ($this->getData('self_accessed')) {
            if ($method !== 'displayTemplate') {
                $compiler->indented('$oldSelf = $context->__get("_self");');
            }
            $compiler->indented('$context->__set("_self", $this);');
        }
        $compiler->compileNode($body);
        if ($this->getData('self_accessed') && $method !== 'displayTemplate') {
            $compiler->indented('$context->__set("_self", $oldSelf);');
        }

        $compiler->outdent();
        $compiler->indented("}\n");
    }
}
