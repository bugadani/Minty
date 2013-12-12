<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use BadMethodCallException;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Tokenizer;
use Modules\Templating\TemplatingOptions;

class Compiler
{
    const MAIN_TEMPLATE = 'Template';

    /**
     * @var TemplatingOptions
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

    public function __construct(Environment $environment)
    {
        $this->parser      = new Parser($environment);
        $this->tokenizer   = new Tokenizer($environment);
        $this->environment = $environment;
        $this->options     = $environment->getOptions();
        $this->filters     = $environment->getFunctions();

        foreach ($environment->getTags() as $tag) {
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
    }

    public function add($string)
    {
        $this->output .= $string;
    }

    public function string($string)
    {
        return "'" . str_replace("'", "\'", $string) . "'";
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
    }

    public function outdent()
    {
        if ($this->indentation == 0) {
            throw new BadMethodCallException('Cannot outdent more.');
        }
        $this->indentation--;
    }

    public function compileNode(Node $node, $indentation = null)
    {
        if ($indentation !== null) {
            $this->indentation = $indentation;
        }
        $node->compile($this);
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

    public function getClassForTemplate($template)
    {
        if (!$template) {
            return 'Modules\Templating\Template';
        }
        $namespace = $this->options->cache_namespace;
        $namespace .= '\\' . strtr($template, '/', '\\');
        return $namespace . 'Template';
    }

    public function compile($template, $class)
    {
        $this->output            = '';
        $this->template_stack    = array();
        $this->output_stack      = array();
        $this->templates         = array();
        $this->extended_template = 'Template';

        $stream = $this->tokenizer->tokenize($template);
        //print_r($stream);exit;
        $nodes  = $this->parser->parse($stream);
        //print_r($nodes);exit;

        $this->addOutputStack();
        $this->compileNode($nodes, 2);
        $main_template = $this->popOutputStack();

        $extended_template = $this->getExtendedTemplate();

        $pos       = strrpos($class, '\\');
        $namespace = substr($class, 0, $pos);
        $classname = substr($class, $pos + 1);

        $use_namespace = $this->getClassForTemplate($extended_template);

        $this->indentation = 0;
        $this->add('<?php');
        $this->newline();
        $this->indented('namespace %s;', $namespace);
        $this->newline();
        $this->indented('use %s as BaseTemplate;', $use_namespace);
        $this->newline();
        $this->indented('class %s extends BaseTemplate', $classname);
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
            $this->addCompiledTemplate('render', $main_template);
        }
        foreach ($this->templates as $name => $template) {
            $this->addCompiledTemplate($name, $template);
        }
        $this->outdent();
        $this->indented('}');
        $this->newline();
        $this->indented('?>');
        return $this->output;
    }
}
