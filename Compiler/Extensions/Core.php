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
use Modules\Templating\Compiler\Operators\BitwiseOperators\BitwiseAndOperator;
use Modules\Templating\Compiler\Operators\BitwiseOperators\BitwiseNotOperator;
use Modules\Templating\Compiler\Operators\BitwiseOperators\BitwiseOrOperator;
use Modules\Templating\Compiler\Operators\BitwiseOperators\BitwiseXorOperator;
use Modules\Templating\Compiler\Operators\BitwiseOperators\ShiftLeftOperator;
use Modules\Templating\Compiler\Operators\BitwiseOperators\ShiftRightOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\EqualsOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\GreaterThanOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\GreaterThanOrEqualsOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\LessThanOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\LessThanOrEqualsOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\NotEqualsOperator;
use Modules\Templating\Compiler\Operators\ConcatenationOperator;
use Modules\Templating\Compiler\Operators\ExclusiveRangeOperator;
use Modules\Templating\Compiler\Operators\ExistenceOperators\IsNotSetOperator;
use Modules\Templating\Compiler\Operators\ExistenceOperators\IsSetOperator;
use Modules\Templating\Compiler\Operators\FilterOperator;
use Modules\Templating\Compiler\Operators\LogicOperators\AndOperator;
use Modules\Templating\Compiler\Operators\LogicOperators\NotOperator;
use Modules\Templating\Compiler\Operators\LogicOperators\OrOperator;
use Modules\Templating\Compiler\Operators\LogicOperators\XorOperator;
use Modules\Templating\Compiler\Operators\PropertyAccessOperator;
use Modules\Templating\Compiler\Operators\RangeOperator;
use Modules\Templating\Compiler\Operators\TestOperators\ContainsOperator;
use Modules\Templating\Compiler\Operators\TestOperators\EmptyOperator;
use Modules\Templating\Compiler\Operators\TestOperators\EndsOperator;
use Modules\Templating\Compiler\Operators\TestOperators\MatchesOperator;
use Modules\Templating\Compiler\Operators\TestOperators\NotContainsOperator;
use Modules\Templating\Compiler\Operators\TestOperators\NotEmptyOperator;
use Modules\Templating\Compiler\Operators\TestOperators\NotEndsOperator;
use Modules\Templating\Compiler\Operators\TestOperators\NotMatchesOperator;
use Modules\Templating\Compiler\Operators\TestOperators\NotStartsOperator;
use Modules\Templating\Compiler\Operators\TestOperators\StartsOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\MinusOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\PlusOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\PostDecrementOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\PostIncrementOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\PreDecrementOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\PreIncrementOperator;
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
            new AdditionOperator(10),
            new SubtractionOperator(10),
            new MultiplicationOperator(11),
            new DivisionOperator(11),
            new RemainderOperator(11),
            new ExponentialOperator(14, Operator::RIGHT),
            //comparison
            new EqualsOperator(7),
            new NotEqualsOperator(7),
            new LessThanOperator(8),
            new LessThanOrEqualsOperator(8),
            new GreaterThanOperator(8),
            new GreaterThanOrEqualsOperator(8),
            //bitwise
            new BitwiseAndOperator(6),
            new BitwiseOrOperator(4),
            new BitwiseXorOperator(5),
            new ShiftLeftOperator(9),
            new ShiftRightOperator(9),
            //logical
            new AndOperator(3),
            new OrOperator(2),
            new XorOperator(1),
            //test
            new ContainsOperator(8, Operator::NONE),
            new EmptyOperator(8, Operator::NONE),
            new EndsOperator(8, Operator::NONE),
            new MatchesOperator(8, Operator::NONE),
            new NotContainsOperator(8, Operator::NONE),
            new NotEmptyOperator(8, Operator::NONE),
            new NotEmptyOperator(8, Operator::NONE),
            new NotEndsOperator(8, Operator::NONE),
            new NotMatchesOperator(8, Operator::NONE),
            new NotStartsOperator(8, Operator::NONE),
            new StartsOperator(8, Operator::NONE),
            //other
            new ConcatenationOperator(10),
            new PropertyAccessOperator(16),
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
            new PreDecrementOperator(13, Operator::RIGHT),
            new PreIncrementOperator(13, Operator::RIGHT),
            new BitwiseNotOperator(13, Operator::RIGHT),
            new MinusOperator(13, Operator::RIGHT),
            new PlusOperator(13, Operator::RIGHT),
            new NotOperator(12, Operator::RIGHT)
        );
        return $operators;
    }

    public function getPostfixUnaryOperators()
    {
        $operators = array(
            new IsSetOperator(15, Operator::RIGHT),
            new IsNotSetOperator(15, Operator::RIGHT),
            new PostDecrementOperator(15),
            new PostIncrementOperator(15),
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
            new MethodFunction('arguments', 'argumentsFunction', true),
            new MethodFunction('batch', 'batchFunction'),
            new SimpleFunction('capitalize', 'ucfirst'),
            new SimpleFunction('count'),
            new MethodFunction('cycle', 'cycleFunction'),
            new MethodFunction('date_format', 'dateFormatFunction'),
            new MethodFunction('first', 'firstFunction'),
            new SimpleFunction('format', 'sprintf'),
            new MethodFunction('join', 'joinFunction'),
            new SimpleFunction('json_encode'),
            new SimpleFunction('keys', 'array_keys'),
            new MethodFunction('last', 'lastFunction'),
            new MethodFunction('length', 'lengthFunction', true),
            new MethodFunction('link_to', 'linkToFunction', true),
            new SimpleFunction('lower', 'strtolower'),
            new SimpleFunction('ltrim'),
            new SimpleFunction('merge', 'array_merge'),
            new SimpleFunction('nl2br', 'nl2br', true),
            new SimpleFunction('number_format'),
            new MethodFunction('pluck', 'pluckFunction'),
            new MethodFunction('random', 'randomFunction'),
            new SimpleFunction('range'),
            new MethodFunction('regexp_replace', 'regexpReplaceFunction'),
            new MethodFunction('replace', 'replaceFunction'),
            new MethodFunction('reverse', 'reverseFunction'),
            new SimpleFunction('rtrim'),
            new MethodFunction('shuffle', 'shuffleFunction'),
            new MethodFunction('slice', 'sliceFunction'),
            new MethodFunction('sort', 'sortFunction'),
            new MethodFunction('spacify', 'spacifyFunction'),
            new MethodFunction('split', 'splitFunction'),
            new SimpleFunction('striptags', 'strip_tags', true),
            new SimpleFunction('title_case', 'ucwords'),
            new SimpleFunction('trim'),
            new MethodFunction('truncate', 'truncateFunction'),
            new SimpleFunction('upper', 'strtoupper'),
            new MethodFunction('url_encode', 'urlEncodeFunction'),
            new MethodFunction('without', 'withoutFunction'),
            new SimpleFunction('wordwrap'),
        );
    }

    public function argumentsFunction(array $args)
    {
        $arglist = '';
        foreach ($args as $name => $value) {
            $arglist .= ' ' . $name . '="' . $value . '"';
        }
        return $arglist;
    }

    public function batchFunction($data, $size, $no_item = null)
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

    public function cycleFunction(&$array)
    {
        $element = each($array);
        if ($element === false) {
            reset($array);
            $element = each($array);
        }
        return $element['value'];
    }

    public function dateFormatFunction($date, $format)
    {
        return date($format, strtotime($date));
    }

    public function firstFunction($data, $number = 1)
    {
        return $this->sliceFunction($data, 0, $number);
    }

    public function joinFunction($data, $glue = '')
    {
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }
        return implode($glue, $data);
    }

    public function lastFunction($data, $number = 1)
    {
        return $this->sliceFunction($data, -$number, null);
    }

    public function lengthFunction($data)
    {
        if (is_string($data)) {
            return strlen($data);
        }
        if (is_array($data) || $data instanceof Countable) {
            return count($data);
        }
        throw new InvalidArgumentException('Reverse expects an array, a string or a Countable instance');
    }

    public function linkToFunction($label, $url, array $args = array())
    {
        $args['href'] = $url;
        return sprintf('<a%s>%s</a>', $this->argumentsFunction($args), $label);
    }

    public function pluckFunction($array, $key)
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

    public function randomFunction($data = null)
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

    public function regexpReplaceFunction($string, $pattern, $replace)
    {
        return preg_replace($pattern, $replace, $string);
    }

    public function replaceFunction($string, $search, $replace)
    {
        return str_replace($search, $replace, $string);
    }

    public function reverseFunction($data, $preserve_keys = false)
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

    public function shuffleFunction($data)
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

    public function sliceFunction($data, $start, $length, $preserve_keys = false)
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

    public function sortFunction($data, $reverse = false)
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

    public function spacifyFunction($string, $delimiter = ' ')
    {
        if (!is_string($string)) {
            throw new InvalidArgumentException('Spacify expects a string.');
        }
        return implode($delimiter, str_split($string));
    }

    public function splitFunction($string, $delimiter = '', $limit = null)
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

    public function truncateFunction($string, $length, $ellipsis = '...')
    {
        if (strlen($string) > $length) {
            $string = $this->firstFunction($string, $length);
            $string .= $ellipsis;
        }
        return $string;
    }

    public function urlEncodeFunction($data, $raw)
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

    public function withoutFunction($data, $without)
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
