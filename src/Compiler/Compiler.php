<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use BadMethodCallException;
use Modules\Templating\Environment;

class Compiler
{
    /**
     * @var Environment
     */
    private $environment;
    private $source;
    private $indentation;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
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

    public function add($string)
    {
        $this->source .= $string;

        return $this;
    }

    public function string($string)
    {
        return "'" . strtr($string, array("'" => "\\'")) . "'";
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
        } elseif (is_numeric($data)) {
            $old = setlocale(LC_NUMERIC, 0);
            if ($old) {
                setlocale(LC_NUMERIC, 'C');
                $this->add($data);
                setlocale(LC_NUMERIC, $old);
            } else {
                $this->add($data);
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

    public function compileNode(Node $node)
    {
        $node->compile($this);

        return $this;
    }

    public function compile(Node $node)
    {
        $this->source = '';

        $node->compile($this);

        return $this->source;
    }
}
