<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Nodes;

use Minty\Compiler\Compiler;
use Minty\Compiler\Node;
use Minty\Environment;

class ClassNode extends Node
{
    private $templateName;
    private $className;
    private $parentTemplateName;
    private $baseClass;

    const MAIN_TEMPLATE_BLOCK = '__main_template_block';

    public function __construct(Environment $env, $templateName)
    {
        $this->templateName = $templateName;
        $this->baseClass    = $env->getOption('template_base_class');

        $className = $env->getTemplateClassName($templateName);

        $classNameOffset = strrpos($className, '\\');
        $this->namespace = substr($className, 0, $classNameOffset);
        $this->className = substr($className, $classNameOffset + 1);
    }

    public function setParentTemplate(Node $parentClass)
    {
        $this->parentTemplateName = $parentClass;
    }

    public function getTemplateName()
    {
        return $this->templateName;
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
            $body = $this->getChild(self::MAIN_TEMPLATE_BLOCK);
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
        $this->removeChild(self::MAIN_TEMPLATE_BLOCK);

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

        $blockNames = array_keys($this->getChildren());
        $blockNames = array_diff($blockNames, array(self::MAIN_TEMPLATE_BLOCK));
        $compiler->indented('$blocks = ')
            ->compileArray($blockNames, false)
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
        if (!$body->hasData('environment_accessed') || $body->getData('environment_accessed')) {
            $compiler->indented('$environment = $this->getEnvironment();');
        }
        $compiler->compileNode($body);
        $compiler->outdent();
        $compiler->indented("}\n");
    }
}
