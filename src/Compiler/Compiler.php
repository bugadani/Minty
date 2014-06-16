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

    public function indented($string)
    {
        $this->source .= "\n";
        $this->source .= str_repeat('    ', $this->indentation);
        if (func_num_args() > 1) {
            $args   = array_slice(func_get_args(), 1);
            $string = vsprintf($string, $args);
        }
        $this->source .= $string;

        return $this;
    }

    public function add($string)
    {
        $this->source .= $string;

        return $this;
    }

    public function compileString($string)
    {
        $string = strtr($string, array("'" => "\\'"));

        return $this->add("'{$string}'");
    }

    public function compileArray(array $array, $writeKeys = true)
    {
        $this->add('array(');
        $this->internalCompileList($array, $writeKeys);

        return $this->add(')');
    }

    public function compileArgumentList(array $arguments)
    {
        $this->add('(');
        $this->internalCompileList($arguments);

        return $this->add(')');
    }

    /**
     * @param array $array
     * @param       $writeKeys
     */
    private function internalCompileList(array $array, $writeKeys = false)
    {
        $first = true;
        foreach ($array as $key => $value) {
            if (!$first) {
                $this->add(', ');
            } else {
                $first = false;
            }
            if ($writeKeys) {
                $this->compileData($key);
                $this->add(' => ');
            }
            $this->compileData($value);
        }
    }

    public function compileData($data)
    {
        if (is_int($data)) {
            $this->add($data);
        } elseif (is_float($data)) {
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
        } elseif (is_array($data)) {
            $this->compileArray($data);
        } elseif ($data instanceof Node) {
            $this->compileNode($data);
        } else {
            $this->compileString($data);
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
        if ($this->indentation === 0) {
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

        $this->compileNode($node);

        return $this->source;
    }
}
