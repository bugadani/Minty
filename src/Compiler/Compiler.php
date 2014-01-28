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
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Tokenizer;
use Modules\Templating\Environment;

class Compiler
{
    const MAIN_TEMPLATE = 'Template';

    /**
     * @var array
     */
    private $options;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Tokenizer
     */
    private $tokenizer;

    /**
     * @var Environment
     */
    private $environment;
    private $output;
    private $indentation;
    private $templates;
    private $template_stack;
    private $extended_template;
    private $embedded;
    private $initialized;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
        $this->options     = $environment->getOptions();
        $this->initialized = false;
    }

    private function initialize()
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;
        $this->parser      = new Parser($this->environment);
        $this->tokenizer   = new Tokenizer($this->environment);
        $this->filters     = $this->environment->getFunctions();

        foreach ($this->environment->getTags() as $tag) {
            $name              = $tag->getTag();
            $this->tags[$name] = $tag;
        }
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
        $this->output .= "\n";
    }

    public function indented($string)
    {
        $args = func_get_args();
        array_shift($args);
        $this->output .= "\n";
        $this->output .= str_repeat(' ', $this->indentation * 4);
        $this->output .= vsprintf($string, $args);
        return $this;
    }

    public function add($string)
    {
        $this->output .= $string;
        return $this;
    }

    public function string($string)
    {
        return "'" . str_replace("'", "\'", $string) . "'";
    }

    public function compileArgumentList(array $arguments)
    {
        $this->add('(');
        $first = true;
        foreach ($arguments as $argument) {
            if ($first) {
                $first = false;
            } else {
                $this->add(', ');
            }
            $this->compileData($argument);
        }
        $this->add(')');
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
            $data->compile($this);
        } else {
            $this->add($this->string($data));
        }
        return $this;
    }

    public function addOutputStack($output = '')
    {
        $this->output_stack[] = $this->output;
        $this->output         = $output;
    }

    public function popOutputStack()
    {
        $output       = $this->output;
        $this->output = array_pop($this->output_stack);
        return $output;
    }

    public function getTemplates()
    {
        return $this->templates;
    }

    public function startTemplate($template)
    {
        $this->template_stack[] = $template . 'Template';
        $this->addOutputStack();
    }

    public function endTemplate()
    {
        $template = array_pop($this->template_stack);

        $this->templates[$template] = $this->popOutputStack();
        return $template;
    }

    public function getCurrentTemplate()
    {
        return end($this->template_stack);
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
        if ($indentation !== null) {
            $old_indentation   = $this->indentation;
            $this->indentation = $indentation;
        }
        $node->compile($this);
        if ($indentation !== null) {
            $this->indentation = $old_indentation;
        }
        return $this;
    }

    public function compileToString(Node $node)
    {
        $this->addOutputStack();
        $node->compile($this);
        return $this->popOutputStack();
    }

    private function addCompiledTemplate($name, $template)
    {
        $this->indented('public function %s()', $name);
        $this->indented('{');
        $this->add($template);
        $this->indented('}');
        $this->newline();
    }

    public function setExtendedTemplate($template)
    {
        $this->extended_template = $template;
    }

    public function extendsTemplate()
    {
        return $this->extended_template !== self::MAIN_TEMPLATE;
    }

    public function getExtendedTemplate()
    {
        if (!$this->extendsTemplate()) {
            return false;
        }
        return $this->extended_template;
    }

    public function getClassForTemplate($template, $include_namespace = true)
    {
        if (!$template) {
            return 'Modules\Templating\Template';
        }
        $path = $this->options['cache_namespace'];
        $path .= '\\' . strtr($template, '/', '\\');

        $pos       = strrpos($path, '\\') + 1;
        $classname = substr($path, $pos);
        if ($include_namespace) {
            $namespace = substr($path, 0, $pos);
            return $namespace . 'Template_' . $classname;
        }
        return 'Template_' . $classname;
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

    public function hasEmbeddedTemplates()
    {
        return !empty($this->embedded);
    }

    public function compile($template, $class)
    {
        $this->output            = '';
        $this->output_stack      = array();
        $this->template_stack    = array('render');
        $this->templates         = array();
        $this->extended_template = 'Template';
        $this->embedded          = array();

        $this->initialize();
        $stream = $this->tokenizer->tokenize($template);
        $nodes  = $this->parser->parse($stream);

        $this->addOutputStack();
        $this->compileNode($nodes, 2);
        $compiled = $this->popOutputStack();

        $pos       = strrpos($class, '\\');
        $namespace = substr($class, 0, $pos);
        $classname = substr($class, $pos + 1);

        $extended_template = $this->getExtendedTemplate();
        $use_namespace     = $this->getClassForTemplate($extended_template);

        $main_class = $this->compileClass($classname, $compiled, $extended_template);

        $this->addOutputStack();
        $this->indentation = 0;
        $this->add('<?php');
        $this->newline();
        $this->indented('namespace %s;', $namespace);
        $this->newline();
        $this->indented('use %s as BaseTemplate;', $use_namespace);

        foreach ($this->embedded as $i => $embedded) {
            $this->indented('use %s as EmbeddedBaseTemplate%d;', $embedded['parent'], $i);
        }

        $this->newline();
        $this->add($main_class);

        $embedded_templates = $this->embedded;
        $this->embedded     = array();
        foreach ($embedded_templates as $i => $embedded) {

            $this->addOutputStack();
            $this->compileNode($embedded['body'], 2);
            $template = $this->popOutputStack();

            $classname    = 'EmbeddedTemplate' . $i;
            $parent_alias = 'EmbeddedBaseTemplate' . $i;

            $this->setExtendedTemplate($embedded['parent']);
            $compiled = $this->compileClass($classname, $template, $embedded['parent'], $parent_alias);
            $this->add($compiled);
        }
        if ($extended_template) {
            $this->setExtendedTemplate($extended_template);
        }
        $this->embedded = $embedded_templates;

        $this->add('?>');
        return $this->popOutputStack();
    }

    private function compileClass($class, $compiled, $extended_template, $parent_class = 'BaseTemplate')
    {
        $this->addOutputStack();
        $this->indented('class %s extends %s', $class, $parent_class);
        $this->indented('{');
        $this->newline();
        $this->indent();
        if ($this->extendsTemplate()) {
            $this->indented('public function getParentTemplate()');
            $this->indented('{');
            $this->indent();
            $this->indented('return \'%s\';', $extended_template);
            $this->outdent();
            $this->indented('}');
            $this->newline();
        } else {
            $this->addCompiledTemplate('render', $compiled);
        }
        $this->indented('public function getEmbeddedTemplates()');
        $this->indented('{');
        $this->indent();
        $this->indented('return array(');
        $this->indent();
        $first = true;
        foreach ($this->getEmbeddedTemplates() as $embedded) {
            if ($first) {
                $first = false;
            } else {
                $this->add(',');
            }
            $this->indented($this->string($embedded['file']));
        }
        $this->outdent();
        $this->indented(');');
        $this->outdent();
        $this->indented('}');
        $this->newline();
        foreach ($this->templates as $name => $template) {
            $this->addCompiledTemplate($name, $template);
        }
        $this->outdent();
        $this->indented('}');
        $this->newline();
        return $this->popOutputStack();
    }
}
