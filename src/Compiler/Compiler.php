<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use BadMethodCallException;
use Modules\Templating\Compiler\Nodes\RootNode;
use Modules\Templating\Environment;

class Compiler
{
    /**
     * @var Environment
     */
    private $environment;
    private $source;
    private $indentation;
    private $templates;
    private $currentTemplate;
    private $extendedTemplate;
    private $embedded;
    private $filters;
    private $tags;
    private $sourceStack;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
        $this->filters     = $environment->getFunctions();
        $this->tags        = $environment->getTags();
    }

    /**
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    public function newline()
    {
        $this->source .= "\n";

        return $this;
    }

    public function indented($string)
    {
        $this->source .= "\n";
        $this->source .= str_repeat('    ', $this->indentation);
        if (func_num_args() > 1) {
            $args = array_slice(func_get_args(), 1);
            $this->source .= vsprintf($string, $args);
        } else {
            $this->source .= $string;
        }

        return $this;
    }

    public function openBracket()
    {
        $this->add(' {');
        $this->indent();

        return $this;
    }

    public function closeBracket()
    {
        $this->outdent();
        $this->indented('}');

        return $this;
    }

    public function bracketed(Node $node)
    {
        $this->openBracket();
        $this->compileNode($node);
        $this->closeBracket();

        return $this;
    }

    public function add($string)
    {
        $this->source .= $string;

        return $this;
    }

    public function string($string)
    {
        return "'" . strtr($string, "'", "\\'") . "'";
    }

    public function compileArgumentList(array $arguments)
    {
        $this->add('(');
        $first = true;
        foreach ($arguments as $argument) {
            if (!$first) {
                $this->add(', ');
            } else {
                $first = false;
            }
            $this->compileData($argument);
        }

        return $this->add(')');
    }

    public function compileData($data)
    {
        if (is_array($data)) {
            $this->add('array(');
            $first = true;
            foreach ($data as $key => $value) {
                if (!$first) {
                    $this->add(', ');
                } else {
                    $first = false;
                }
                $this->compileData($key);
                $this->add(' => ');
                $this->compileData($value);
            }
            $this->add(')');
        } elseif (is_numeric($data) || is_float($data)) {
            $old = setlocale(LC_NUMERIC, 0);
            if ($old) {
                setlocale(LC_NUMERIC, 'C');
            }
            $this->add($data);
            if ($old) {
                setlocale(LC_NUMERIC, $old);
            }
        } elseif (is_bool($data)) {
            $this->add($data ? 'true' : 'false');
        } elseif ($data === null) {
            $this->add('null');
        } elseif ($data instanceof Node) {
            $this->compileNode($data);
        } else {
            $this->add($this->string($data));
        }

        return $this;
    }

    private function addSourceStack($output = '')
    {
        $this->sourceStack[] = $this->source;
        $this->source        = $output;
    }

    private function popSourceStack()
    {
        $source       = $this->source;
        $this->source = array_pop($this->sourceStack);

        return $source;
    }

    public function getTemplates()
    {
        return $this->templates;
    }

    public function addTemplate($templateName, Node $node)
    {
        $templateMethodName = $templateName . 'Template';

        $this->templates[$templateMethodName] = $node;

        return $templateMethodName;
    }

    public function indent()
    {
        $this->indentation++;

        return $this;
    }

    public function outdent()
    {
        if ($this->indentation == 0) {
            throw new BadMethodCallException('Cannot outdent more.');
        }
        $this->indentation--;

        return $this;
    }

    public function compileNode(Node $node, $indentation = null)
    {
        $old_indentation   = $this->indentation;
        $this->indentation = $indentation ? : $this->indentation;
        $node->compile($this);
        $this->indentation = $old_indentation;

        return $this;
    }

    public function compileToString(Node $node, $indentation = null)
    {
        $this->addSourceStack();
        $this->compileNode($node, $indentation);

        return $this->popSourceStack();
    }

    public function setExtendedTemplate($template)
    {
        $this->extendedTemplate = $template;
    }

    public function extendsTemplate()
    {
        return $this->extendedTemplate !== null;
    }

    public function getExtendedTemplate()
    {
        return $this->extendedTemplate;
    }

    public function getClassForTemplate($template, $include_namespace = true)
    {
        if (!$template) {
            return 'Modules\Templating\Template';
        }
        $path = $this->environment->getOption('cache_namespace');
        $path .= '\\' . strtr($template, '/', '\\');

        $pos       = strrpos($path, '\\') + 1;
        $className = substr($path, $pos);
        if ($include_namespace) {
            $namespace = substr($path, 0, $pos);

            return $namespace . 'Template_' . $className;
        }

        return 'Template_' . $className;
    }

    public function addEmbedded($parent, RootNode $root)
    {
        $this->embedded[] = array(
            'parent' => $this->getClassForTemplate($parent),
            'file'   => $parent,
            'body'   => $root
        );

        return 'EmbeddedTemplate' . (count($this->embedded) - 1);
    }

    public function getEmbeddedTemplates()
    {
        return $this->embedded;
    }

    public function getEmbeddedTemplateNames()
    {
        $names = array();
        foreach ($this->embedded as $embedded) {
            $names[] = $embedded['file'];
        }

        return $names;
    }

    public function hasEmbeddedTemplates()
    {
        return !empty($this->embedded);
    }

    public function getCurrentTemplate()
    {
        return $this->currentTemplate;
    }

    public function compile(Node $node, $class)
    {
        $this->source           = '';
        $this->currentTemplate  = 'render';
        $this->sourceStack      = array();
        $this->templates        = array();
        $this->extendedTemplate = null;
        $this->embedded         = array();

        $pos       = strrpos($class, '\\');
        $namespace = substr($class, 0, $pos);
        $className = substr($class, $pos + 1);

        $mainTemplateSource = $this->compileForClass($node, $className);

        $this->addSourceStack();
        $this->indentation = 0;
        $this->add('<?php');
        $this->newline();
        $this->indented('namespace %s;', ltrim($namespace, '\\'));
        $this->newline();

        $embeddedTemplates = $this->getEmbeddedTemplates();
        foreach ($embeddedTemplates as $i => $embedded) {
            $this->indented('use %s as EmbeddedBaseTemplate%d;', $embedded['parent'], $i);
        }

        $this->newline();
        $this->add($mainTemplateSource);

        $extendedTemplate = $this->getExtendedTemplate();
        $templates        = $this->templates;
        $this->embedded   = array();
        $this->templates  = array();
        foreach ($embeddedTemplates as $i => $embedded) {
            $className   = 'EmbeddedTemplate' . $i;
            $parentAlias = 'EmbeddedBaseTemplate' . $i;

            $this->setExtendedTemplate($embedded['parent']);

            $this->add(
                $this->compileForClass(
                    $embedded['body'],
                    $className,
                    $parentAlias
                )
            );
        }
        $this->setExtendedTemplate($extendedTemplate);
        $this->templates = $templates;
        $this->embedded  = $embeddedTemplates;

        return $this->popSourceStack();
    }

    /**
     * @param Node        $node
     * @param             $className
     * @param string|null $parentClassName
     *
     * @return array
     */
    private function compileForClass(Node $node, $className, $parentClassName = null)
    {
        $source           = $this->compileToString($node, 2);
        $extendedTemplate = $this->getExtendedTemplate();

        return $this->compileClass(
            $className,
            $source,
            $extendedTemplate,
            $parentClassName ? : '\\' . $this->getClassForTemplate($extendedTemplate)
        );
    }

    private function addCompiledTemplateMethod($name, $template)
    {
        $this->indented('public function %s()', $name);
        $this->indented('{');
        $this->add($template);
        $this->indented('}');
        $this->newline();
    }

    private function compileClass($class, $body, $extendedTemplate, $parentClass = 'BaseTemplate')
    {
        $this->addSourceStack();
        $this->indented('class %s extends %s', $class, $parentClass);
        $this->indented('{');
        $this->newline();
        $this->indent();

        if ($this->extendsTemplate()) {
            $this->indented('public function getParentTemplate()');
            $this->indented('{');
            $this->indent();
            $this->indented('return \'%s\';', $extendedTemplate);
            $this->outdent();
            $this->indented('}');
            $this->newline();
        } else {
            $this->addCompiledTemplateMethod('render', $body);
        }

        foreach ($this->templates as $name => $templateNode) {
            $this->currentTemplate = $name;
            $this->addCompiledTemplateMethod($name, $this->compileToString($templateNode, 2));
        }

        $this->compileEmbeddedTemplateMethod();

        $this->outdent();
        $this->indented('}');
        $this->newline();

        return $this->popSourceStack();
    }

    private function compileEmbeddedTemplateMethod()
    {
        $embeddedTemplates = $this->getEmbeddedTemplates();
        if (empty($embeddedTemplates)) {
            return;
        }
        $this->indented('public function getEmbeddedTemplates()');
        $this->indented('{');
        $this->indent();
        $this->indented('return array(');
        $this->indent();
        $first = true;
        foreach ($embeddedTemplates as $embedded) {
            if ($first) {
                $first = false;
            } else {
                $this->add(', ');
            }
            $this->indented($this->string($embedded['file']));
        }
        $this->outdent();
        $this->indented(');');
        $this->outdent();
        $this->indented('}');
        $this->newline();
    }
}
