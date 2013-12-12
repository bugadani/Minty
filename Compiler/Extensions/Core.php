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
use Modules\Templating\Compiler\Operator;
use Modules\Templating\Compiler\Operators\ArithmeticOperators\AdditionOperator;
use Modules\Templating\Compiler\Operators\ArithmeticOperators\DivisionOperator;
use Modules\Templating\Compiler\Operators\ArithmeticOperators\ExponentialOperator;
use Modules\Templating\Compiler\Operators\ArithmeticOperators\MultiplicationOperator;
use Modules\Templating\Compiler\Operators\ArithmeticOperators\RemainderOperator;
use Modules\Templating\Compiler\Operators\ArithmeticOperators\SubtractionOperator;
use Modules\Templating\Compiler\Operators\ArrowOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\EqualsOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\GreaterThanOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\GreaterThanOrEqualsOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\LessThanOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\LessThanOrEqualsOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\NotEqualsOperator;
use Modules\Templating\Compiler\Operators\ExclusiveRangeOperator;
use Modules\Templating\Compiler\Operators\ExistenceOperators\IsNotSetOperator;
use Modules\Templating\Compiler\Operators\ExistenceOperators\IsSetOperator;
use Modules\Templating\Compiler\Operators\FilterOperator;
use Modules\Templating\Compiler\Operators\MinusOperator;
use Modules\Templating\Compiler\Operators\RangeOperator;
use Modules\Templating\Compiler\Tags\AssignTag;
use Modules\Templating\Compiler\Tags\ForTag;
use Modules\Templating\Compiler\Tags\IfTag;
use Modules\Templating\Compiler\Tags\ListTag;
use Modules\Templating\Compiler\Tags\OutputTag;
use Modules\Templating\Compiler\Tags\SwitchTag;
use Modules\Templating\Compiler\Tags\TemplateExtension\BlockTag;
use Modules\Templating\Compiler\Tags\TemplateExtension\ExtendsTag;
use Modules\Templating\Compiler\Tags\TemplateExtension\ParentTag;
use Modules\Templating\Extension;
use Traversable;

class Core extends Extension
{

    public function getExtensionName()
    {
        return 'core';
    }

    public function getBinaryOperators()
    {
        $binary_operators = array(
            //arithmetic operators
            new AdditionOperator(2),
            new SubtractionOperator(2),
            new MultiplicationOperator(3),
            new DivisionOperator(3),
            new RemainderOperator(3),
            new ExponentialOperator(3, Operator::RIGHT),
            //comparison
            new EqualsOperator(1),
            new NotEqualsOperator(1),
            new LessThanOperator(1),
            new LessThanOrEqualsOperator(1),
            new GreaterThanOperator(1),
            new GreaterThanOrEqualsOperator(1),
            //other
            new FilterOperator(1),
            new RangeOperator(1),
            new ExclusiveRangeOperator(1),
            new ArrowOperator(1),
        );
        return $binary_operators;
    }

    public function getPrefixUnaryOperators()
    {
        $operators = array(
            new MinusOperator(4),
        );
        return $operators;
    }

    public function getPostfixUnaryOperators()
    {
        $operators = array(
            new IsSetOperator(4, Operator::RIGHT),
            new IsNotSetOperator(4, Operator::RIGHT),
        );
        return $operators;
    }

    public function getTags()
    {
        $tags = array(
            new ListTag(),
            new ForTag(),
            new SwitchTag(),
            new BlockTag(),
            new ParentTag(),
            new ExtendsTag(),
            new IfTag(),
            new AssignTag(),
            new OutputTag(),
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
            new MethodFunction('date_format', 'filter_date_format'),
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
            new MethodFunction('truncate', 'filter_truncate'),
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

    public function filter_date_format($date, $format)
    {
        return date($format, strtotime($date));
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
            usort($data, function () {
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
            $string = $this->filter_first($string, $length);
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
