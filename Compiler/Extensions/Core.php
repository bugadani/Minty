<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Compiler\Extensions;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Modules\Templating\Compiler\Functions\MethodFunction;
use Modules\Templating\Compiler\Functions\SimpleFunction;
use Modules\Templating\Compiler\Operators\AndOperator;
use Modules\Templating\Compiler\Operators\ArithmeticOperators;
use Modules\Templating\Compiler\Operators\ArrowOperator;
use Modules\Templating\Compiler\Operators\ColonOperator;
use Modules\Templating\Compiler\Operators\CommaOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\EqualOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\GreaterOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\GreaterOrEqualOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\LessOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\LessOrEqualOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\NotEqualOperator;
use Modules\Templating\Compiler\Operators\ConcatenationOperator;
use Modules\Templating\Compiler\Operators\DoubleArrowOperator;
use Modules\Templating\Compiler\Operators\EndsWithOperator;
use Modules\Templating\Compiler\Operators\IsOperator;
use Modules\Templating\Compiler\Operators\MatchesOperator;
use Modules\Templating\Compiler\Operators\MinusOperator;
use Modules\Templating\Compiler\Operators\OrOperator;
use Modules\Templating\Compiler\Operators\ParenthesisOperators\BracketOperator;
use Modules\Templating\Compiler\Operators\ParenthesisOperators\ParenthesisOperator;
use Modules\Templating\Compiler\Operators\PeriodOperator;
use Modules\Templating\Compiler\Operators\PipeOperator;
use Modules\Templating\Compiler\Operators\PlusOperator;
use Modules\Templating\Compiler\Operators\PowerOperator;
use Modules\Templating\Compiler\Operators\RangeOperator;
use Modules\Templating\Compiler\Operators\StartsWithOperator;
use Modules\Templating\Compiler\Operators\StringOperator;
use Modules\Templating\Compiler\Operators\TestOperators\DivisibleByOperator;
use Modules\Templating\Compiler\Operators\TestOperators\EmptyOperator;
use Modules\Templating\Compiler\Operators\TestOperators\EvenOperator;
use Modules\Templating\Compiler\Operators\TestOperators\LikeOperator;
use Modules\Templating\Compiler\Operators\TestOperators\OddOperator;
use Modules\Templating\Compiler\Operators\TestOperators\SameAsOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\DecrementOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\IncrementOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\NotOperator;
use Modules\Templating\Compiler\Tags\Blocks\BlockBlock;
use Modules\Templating\Compiler\Tags\Blocks\ForBlock;
use Modules\Templating\Compiler\Tags\Blocks\IfBlock;
use Modules\Templating\Compiler\Tags\Blocks\SwitchBlock;
use Modules\Templating\Compiler\Tags\Blocks\TemplateBlock;
use Modules\Templating\Compiler\Tags\CaseTag;
use Modules\Templating\Compiler\Tags\ElseIfTag;
use Modules\Templating\Compiler\Tags\ElseTag;
use Modules\Templating\Compiler\Tags\ExtendsTag;
use Modules\Templating\Compiler\Tags\ListTag;
use Modules\Templating\Compiler\Tags\ParentTag;
use Modules\Templating\Extension;
use Traversable;

class Core extends Extension
{

    public function getExtensionName()
    {
        return 'core';
    }

    public function getOperators()
    {
        $operators = array(
            new ArrowOperator(),
            new IsOperator(),
            new StringOperator(),
            new BracketOperator(),
            new ParenthesisOperator(),
            new CommaOperator(),
            new PeriodOperator(),
            new PipeOperator(),
            new NotOperator(),
            new DoubleArrowOperator(),
            new ColonOperator(),
            new ConcatenationOperator(),
            //arithmetic operators
            new ArithmeticOperators(),
            new IncrementOperator(),
            new DecrementOperator(),
            new PlusOperator(),
            new MinusOperator(),
            //logic operators
            new AndOperator(),
            new OrOperator(),
            //comparison operators
            new EqualOperator(),
            new GreaterOperator(),
            new GreaterOrEqualOperator(),
            new LessOperator(),
            new LessOrEqualOperator(),
            new NotEqualOperator(),
            //test operators
            new EmptyOperator(),
            new EvenOperator(),
            new OddOperator(),
            new LikeOperator(),
            new DivisibleByOperator(),
            new SameAsOperator(),
            new StartsWithOperator(),
            new EndsWithOperator(),
            new MatchesOperator(),
        );
        return $operators;
    }

    public function getTags()
    {
        $tags = array(
            new IfBlock(),
            new ForBlock(),
            new TemplateBlock(),
            new BlockBlock(),
            new SwitchBlock(),
            new ParentTag(),
            new ListTag(),
            new CaseTag(),
            new ElseTag(),
            new ElseIfTag(),
            new ExtendsTag(),
        );
        return $tags;
    }

    public function getFunctions()
    {
        return array(
            new SimpleFunction('abs'),
            new MethodFunction('arguments', 'filter_arguments', true),
            new MethodFunction('batch', 'filter_batch'),
            new SimpleFunction('capitalize', 'ucfirst'),
            new SimpleFunction('count'),
            new MethodFunction('cycle', 'filter_cycle'),
            new MethodFunction('first', 'filter_first'),
            new SimpleFunction('format', 'sprintf'),
            new MethodFunction('join', 'filter_join'),
            new SimpleFunction('json_encode'),
            new SimpleFunction('keys', 'array_keys'),
            new MethodFunction('last', 'filter_last'),
            new MethodFunction('length', 'filter_length', true),
            new MethodFunction('link_to', 'filter_link_to', true),
            new SimpleFunction('lower', 'strtolower'),
            new SimpleFunction('ltrim'),
            new SimpleFunction('merge', 'array_merge'),
            new SimpleFunction('nl2br', 'nl2br', true),
            new SimpleFunction('number_format'),
            new MethodFunction('pluck', 'filter_pluck'),
            new MethodFunction('random', 'filter_random'),
            new SimpleFunction('range'),
            new MethodFunction('replace', 'filter_replace'),
            new MethodFunction('reverse', 'filter_reverse'),
            new SimpleFunction('rtrim'),
            new MethodFunction('shuffle', 'filter_shuffle'),
            new MethodFunction('slice', 'filter_slice'),
            new MethodFunction('sort', 'filter_sort'),
            new MethodFunction('spacify', 'filter_spacify'),
            new MethodFunction('split', 'filter_split'),
            new SimpleFunction('striptags', 'strip_tags', true),
            new SimpleFunction('title_case', 'ucwords'),
            new SimpleFunction('trim'),
            new MethodFunction('truncare', 'filter_truncare'),
            new SimpleFunction('upper', 'strtoupper'),
            new MethodFunction('url_encode', 'filter_url_encode'),
            new MethodFunction('without', 'filter_without'),
            new SimpleFunction('wordwrap'),
        );
    }

    public function filter_arguments(array $args)
    {
        $arglist = '';
        foreach ($args as $name => $value) {
            $arglist .= ' ' . $name . '="' . $value . '"';
        }
        return $arglist;
    }

    public function filter_batch($data, $size, $no_item = null)
    {
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }
        $result = array_chunk($data, abs($size), true);
        if ($no_item == null) {
            return $result;
        }
        $last          = count($result) - 1;
        $result[$last] = array_pad($result[$last], $size, $no_item);
        return $result;
    }

    public function filter_cycle(&$array)
    {
        $element = each($array);
        if ($element === false) {
            reset($array);
            $element = each($array);
        }
        return $element['value'];
    }

    public function filter_first($data, $number = 1)
    {
        return $this->filter_slice($data, 0, $number);
    }

    public function filter_join($data, $glue = '')
    {
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }
        return implode($glue, $data);
    }

    public function filter_last($data, $number = 1)
    {
        return $this->filter_slice($data, -$number, null);
    }

    public function filter_length($data)
    {
        if (is_string($data)) {
            return strlen($data);
        }
        if (is_array($data) || $data instanceof Countable) {
            return count($data);
        }
        throw new InvalidArgumentException('Reverse expects an array, a string or a Countable instance');
    }

    public function filter_link_to($label, $url, array $args = array())
    {
        $args['href'] = $url;
        return sprintf('<a%s>%s</a>', $this->filter_arguments($args), $label);
    }

    public function filter_pluck($array, $key)
    {
        if (!is_array($array) && !$array instanceof Traversable) {
            throw new InvalidArgumentException('Pluck expects a two-dimensional array as the first argument.');
        }
        $return = array();

        foreach ($array as $element) {
            if (is_array($element) || $element instanceof ArrayAccess) {
                if (isset($element[$key])) {
                    $return[] = $element[$key];
                }
            }
        }
        return $return;
    }

    public function filter_random($data = null)
    {
        if ($data === null) {
            return rand();
        }
        if (is_numeric($data)) {
            return rand(0, $data);
        }
        if (is_string($data)) {
            $data = str_split($data);
        }
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }
        if (is_array($data)) {
            return $data[array_rand($data)];
        }
        throw new InvalidArgumentException('Random expects an array, a number or a string');
    }

    public function filter_regexp_replace($string, $pattern, $replace)
    {
        return preg_replace($pattern, $replace, $string);
    }

    public function filter_replace($string, $search, $replace)
    {
        return str_replace($search, $replace, $string);
    }

    public function filter_reverse($data, $preserve_keys = false)
    {
        if (is_string($data)) {
            return strrev($data);
        }
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }
        if (is_array($data)) {
            return array_reverse($data, $preserve_keys);
        }
        throw new InvalidArgumentException('Reverse expects an array or a string');
    }

    public function filter_shuffle($data)
    {
        if (is_string($data)) {
            return str_shuffle($data);
        }
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }
        if (is_array($data)) {
            usort($data, function() {
                return rand() > rand();
            });
            return $data;
        }
        throw new InvalidArgumentException('Shuffle expects an array or a string');
    }

    public function filter_slice($data, $start, $length, $preserve_keys = false)
    {
        if (is_string($data)) {
            if ($length === null) {
                return substr($data, $start);
            } else {
                return substr($data, $start, $length);
            }
        }
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        } if (is_array($data)) {
            return array_slice($data, $start, $length, $preserve_keys);
        }
        throw new InvalidArgumentException('Slice expects an array or a string');
    }

    public function filter_sort($data, $reverse = false)
    {
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }
        if (is_array($data)) {
            if ($reverse) {
                arsort($data);
            } else {
                asort($data);
            }
            return $data;
        }
        throw new InvalidArgumentException('Sort expects an array');
    }

    public function filter_spacify($string, $delimiter = ' ')
    {
        if (!is_string($string)) {
            throw new InvalidArgumentException('Spacify expects a string.');
        }
        return implode($delimiter, str_split($string));
    }

    public function filter_split($string, $delimiter = '', $limit = null)
    {
        if (!is_string($string)) {
            throw new InvalidArgumentException('Split expects a string');
        }
        if ($delimiter === '') {
            return str_split($string, $limit ? : 1);
        } elseif ($limit === null) {
            return explode($delimiter, $string);
        }
        return explode($delimiter, $string, $limit);
    }

    public function filter_truncate($string, $length, $ellipsis = '...')
    {
        if (strlen($string) > $length) {
            $string = $this->first($string, $length);
            $string .= $ellipsis;
        }
        return $string;
    }

    public function filter_url_encode($data, $raw)
    {
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }
        if (is_array($data)) {
            return http_build_query($data, '', '&');
        }
        if ($raw) {
            return rawurlencode($data);
        }
        return urlencode($data);
    }

    public function filter_without($data, $without)
    {
        if (is_string($data)) {
            if (!is_string($without) && !is_array($without)) {
                if ($without instanceof Traversable) {
                    $without = iterator_to_array($without);
                } else {
                    throw new InvalidArgumentException('Without expects string or array arguments.');
                }
            }
            return str_replace($without, '', $data);
        }
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }
        if (is_array($data)) {
            if (!is_array($without)) {
                if ($without instanceof Traversable) {
                    $without = iterator_to_array($without);
                } else {
                    $without = array($without);
                }
            }
            return array_diff($data, $without);
        }
    }
}
