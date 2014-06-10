<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Extensions;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Modules\Templating\Compiler\Operator;
use Modules\Templating\Compiler\Operators\ArithmeticOperators\AdditionOperator;
use Modules\Templating\Compiler\Operators\ArithmeticOperators\DivisionOperator;
use Modules\Templating\Compiler\Operators\ArithmeticOperators\ExponentialOperator;
use Modules\Templating\Compiler\Operators\ArithmeticOperators\MultiplicationOperator;
use Modules\Templating\Compiler\Operators\ArithmeticOperators\RemainderOperator;
use Modules\Templating\Compiler\Operators\ArithmeticOperators\SubtractionOperator;
use Modules\Templating\Compiler\Operators\BitwiseOperators\BitwiseAndOperator;
use Modules\Templating\Compiler\Operators\BitwiseOperators\BitwiseNotOperator;
use Modules\Templating\Compiler\Operators\BitwiseOperators\BitwiseOrOperator;
use Modules\Templating\Compiler\Operators\BitwiseOperators\BitwiseXorOperator;
use Modules\Templating\Compiler\Operators\BitwiseOperators\ShiftLeftOperator;
use Modules\Templating\Compiler\Operators\BitwiseOperators\ShiftRightOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\EqualsOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\GreaterThanOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\GreaterThanOrEqualsOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\IdenticalOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\LessThanOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\LessThanOrEqualsOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\NotEqualsOperator;
use Modules\Templating\Compiler\Operators\ComparisonOperators\NotIdenticalOperator;
use Modules\Templating\Compiler\Operators\ConcatenationOperator;
use Modules\Templating\Compiler\Operators\ExclusiveRangeOperator;
use Modules\Templating\Compiler\Operators\ExistenceOperators\IsNotSetOperator;
use Modules\Templating\Compiler\Operators\ExistenceOperators\IsSetOperator;
use Modules\Templating\Compiler\Operators\FalseCoalescingOperator;
use Modules\Templating\Compiler\Operators\FilterOperator;
use Modules\Templating\Compiler\Operators\LogicOperators\AndOperator;
use Modules\Templating\Compiler\Operators\LogicOperators\NotOperator;
use Modules\Templating\Compiler\Operators\LogicOperators\OrOperator;
use Modules\Templating\Compiler\Operators\LogicOperators\XorOperator;
use Modules\Templating\Compiler\Operators\PropertyAccessOperator;
use Modules\Templating\Compiler\Operators\RangeOperator;
use Modules\Templating\Compiler\Operators\TestOperators\ContainsOperator;
use Modules\Templating\Compiler\Operators\TestOperators\DivisibleByOperator;
use Modules\Templating\Compiler\Operators\TestOperators\EndsOperator;
use Modules\Templating\Compiler\Operators\TestOperators\MatchesOperator;
use Modules\Templating\Compiler\Operators\TestOperators\NotContainsOperator;
use Modules\Templating\Compiler\Operators\TestOperators\NotDivisibleByOperator;
use Modules\Templating\Compiler\Operators\TestOperators\NotEndsOperator;
use Modules\Templating\Compiler\Operators\TestOperators\NotMatchesOperator;
use Modules\Templating\Compiler\Operators\TestOperators\NotStartsOperator;
use Modules\Templating\Compiler\Operators\TestOperators\StartsOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\EmptyOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\EvenOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\MinusOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\NotEmptyOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\OddOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\PlusOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\PostDecrementOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\PostIncrementOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\PreDecrementOperator;
use Modules\Templating\Compiler\Operators\UnaryOperators\PreIncrementOperator;
use Modules\Templating\Compiler\Tags\CaptureTag;
use Modules\Templating\Compiler\Tags\CaseTag;
use Modules\Templating\Compiler\Tags\DoTag;
use Modules\Templating\Compiler\Tags\ElseIfTag;
use Modules\Templating\Compiler\Tags\ElseTag;
use Modules\Templating\Compiler\Tags\ExtractTag;
use Modules\Templating\Compiler\Tags\ForTag;
use Modules\Templating\Compiler\Tags\IfTag;
use Modules\Templating\Compiler\Tags\ListTag;
use Modules\Templating\Compiler\Tags\PrintTag;
use Modules\Templating\Compiler\Tags\SwitchTag;
use Modules\Templating\Compiler\Tags\TemplateExtension\BlockTag;
use Modules\Templating\Compiler\Tags\TemplateExtension\EmbedTag;
use Modules\Templating\Compiler\Tags\TemplateExtension\ExtendsTag;
use Modules\Templating\Compiler\Tags\TemplateExtension\IncludeTag;
use Modules\Templating\Compiler\Tags\TemplateExtension\ParentTag;
use Modules\Templating\Compiler\TemplateFunction;
use Modules\Templating\Context;
use Modules\Templating\Environment;
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
        $binaryOperators = array(
            //arithmetic operators
            new AdditionOperator(10),
            new SubtractionOperator(10),
            new MultiplicationOperator(11),
            new DivisionOperator(11),
            new RemainderOperator(11),
            new ExponentialOperator(14, Operator::RIGHT),
            //comparison
            new EqualsOperator(7),
            new IdenticalOperator(7),
            new NotIdenticalOperator(7),
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
            new DivisibleByOperator(8, Operator::NONE),
            //other
            new FalseCoalescingOperator(1),
            new ConcatenationOperator(10),
            new PropertyAccessOperator(16),
            new FilterOperator(11),
            new RangeOperator(9),
            new ExclusiveRangeOperator(9),
        );

        return $binaryOperators;
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
            new EvenOperator(15, Operator::NONE),
            new OddOperator(15, Operator::NONE),
            new DivisibleByOperator(15, Operator::NONE),
            new NotDivisibleByOperator(15, Operator::NONE),
            new PostDecrementOperator(15),
            new PostIncrementOperator(15),
        );

        return $operators;
    }

    public function getTags()
    {
        $tags = array(
            new BlockTag(),
            new CaptureTag(),
            new CaseTag(),
            new DoTag(),
            new ElseIfTag(),
            new ElseTag(),
            new EmbedTag(),
            new ExtendsTag(),
            new ExtractTag(),
            new ForTag(),
            new IfTag(),
            new IncludeTag(),
            new ListTag(),
            new ParentTag(),
            new PrintTag(),
            new SwitchTag(),
        );

        return $tags;
    }

    public function getFunctions()
    {
        $ns = '\\' . __NAMESPACE__;

        return array(
            new TemplateFunction('abs'),
            new TemplateFunction('arguments', $ns . '\template_function_arguments', array('is_safe' => true)),
            new TemplateFunction('batch', $ns . '\template_function_batch'),
            new TemplateFunction('capitalize', 'ucfirst'),
            new TemplateFunction('count'),
            new TemplateFunction('cycle', $ns . '\template_function_cycle'),
            new TemplateFunction('default', null, array('compiler' => '\Modules\Templating\Extensions\Compilers\DefaultCompiler')),
            new TemplateFunction('date_format', $ns . '\template_function_dateFormat'),
            new TemplateFunction('extract', $ns . '\template_function_extract', array('needs_context' => true)),
            new TemplateFunction('first', $ns . '\template_function_first'),
            new TemplateFunction('format', 'sprintf'),
            new TemplateFunction('is_int'),
            new TemplateFunction('is_numeric'),
            new TemplateFunction('is_string'),
            new TemplateFunction('join', $ns . '\template_function_join'),
            new TemplateFunction('json_encode'),
            new TemplateFunction('keys', 'array_keys'),
            new TemplateFunction('last', $ns . '\template_function_last'),
            new TemplateFunction('length', $ns . '\template_function_length', array('is_safe' => true)),
            new TemplateFunction('link_to', $ns . '\template_function_linkTo', array('is_safe' => true)),
            new TemplateFunction('lower', 'strtolower'),
            new TemplateFunction('ltrim'),
            new TemplateFunction('max', $ns . '\template_function_max'),
            new TemplateFunction('merge', 'array_merge'),
            new TemplateFunction('min', $ns . '\template_function_min'),
            new TemplateFunction('nl2br', 'nl2br', array('is_safe' => true)),
            new TemplateFunction('number_format'),
            new TemplateFunction('pluck', $ns . '\template_function_pluck'),
            new TemplateFunction('random', $ns . '\template_function_random'),
            new TemplateFunction('range'),
            new TemplateFunction('raw', $ns . '\template_function_raw', array('is_safe' => true)),
            new TemplateFunction('regexp_replace', $ns . '\template_function_regexpReplace'),
            new TemplateFunction('replace', $ns . '\template_function_replace'),
            new TemplateFunction('reverse', $ns . '\template_function_reverse'),
            new TemplateFunction('rtrim'),
            new TemplateFunction('shuffle', $ns . '\template_function_shuffle'),
            new TemplateFunction('slice', $ns . '\template_function_slice'),
            new TemplateFunction('sort', $ns . '\template_function_sort'),
            new TemplateFunction('source', $ns . '\template_function_source', array('needs_environment' => true)),
            new TemplateFunction('spacify', $ns . '\template_function_spacify'),
            new TemplateFunction('split', $ns . '\template_function_split'),
            new TemplateFunction('striptags', 'strip_tags', array('is_safe' => true)),
            new TemplateFunction('title_case', 'ucwords'),
            new TemplateFunction('trim'),
            new TemplateFunction('truncate', $ns . '\template_function_truncate'),
            new TemplateFunction('upper', 'strtoupper'),
            new TemplateFunction('url_encode', $ns . '\template_function_urlEncode'),
            new TemplateFunction('without', $ns . '\template_function_without'),
            new TemplateFunction('wordwrap'),
        );
    }
}

/* Helper functions */

/**
 * @param $data
 *
 * @return array
 * @throws \InvalidArgumentException
 */
function traversableToArray($data)
{
    if ($data instanceof Traversable) {
        return iterator_to_array($data);
    }
    if (is_array($data)) {
        return $data;
    }

    throw new InvalidArgumentException('Expected an array or traversable object.');
}

/* Template functions */

function template_function_arguments(array $args)
{
    $string = '';
    foreach ($args as $name => $value) {
        $string .= " {$name}=\"{$value}\"";
    }

    return $string;
}

function template_function_batch($data, $size, $preserveKeys = true, $noItem = null)
{
    $data   = traversableToArray($data);
    $result = array_chunk($data, abs($size), $preserveKeys);
    if ($noItem == null) {
        return $result;
    }
    $last          = count($result) - 1;
    $result[$last] = array_pad($result[$last], $size, $noItem);

    return $result;
}

function template_function_cycle(&$array)
{
    $element = each($array);
    if ($element === false) {
        reset($array);
        $element = each($array);
    }

    return $element['value'];
}

function template_function_dateFormat($date, $format)
{
    return date($format, strtotime($date));
}

function template_function_extract(Context $context, $source, $keys)
{
    if (is_string($keys)) {
        $keys = array($keys);
    }
    foreach ($keys as $key) {
        $context->$key = $context->getProperty($source, $key);
    }
}

function template_function_first($data, $number = 1)
{
    return template_function_slice($data, 0, $number);
}

function template_function_join($data, $glue = '')
{
    $data = traversableToArray($data);

    return implode($glue, $data);
}

function template_function_last($data, $number = 1)
{
    return template_function_slice($data, -$number, null);
}

function template_function_length($data)
{
    if (is_string($data)) {
        return strlen($data);
    }
    if (is_array($data) || $data instanceof Countable) {
        return count($data);
    }
    throw new InvalidArgumentException('Reverse expects an array, a string or a Countable instance');
}

function template_function_linkTo($label, $url, array $args = array())
{
    $args['href'] = $url;

    $arguments = template_function_arguments($args);

    return "<a{$arguments}>{$label}</a>";
}

function template_function_max()
{
    $values = func_get_args();
    $max    = null;
    foreach ($values as $value) {
        if ($value > $max || $max === null) {
            $max = $value;
        }
    }

    return $max;
}

function template_function_min()
{
    $min = null;
    foreach (func_get_args() as $value) {
        if ($value < $min || $min === null) {
            $min = $value;
        }
    }

    return $min;
}

function template_function_pluck($array, $key)
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

function template_function_random($data = null)
{
    if ($data === null) {
        return rand();
    }
    if (is_numeric($data)) {
        return rand(0, $data);
    }
    if (is_string($data)) {
        $data = str_split($data);
    } else {
        $data = traversableToArray($data);
    }

    return $data[array_rand($data)];
}

function template_function_raw($data)
{
    return $data;
}

function template_function_regexpReplace($string, $pattern, $replace)
{
    return preg_replace($pattern, $replace, $string);
}

function template_function_replace($string, $search, $replace)
{
    return str_replace($search, $replace, $string);
}

function template_function_reverse($data, $preserveKeys = false)
{
    if (is_string($data)) {
        return strrev($data);
    }
    $data = traversableToArray($data);

    return array_reverse($data, $preserveKeys);
}

function template_function_shuffle($data)
{
    if (is_string($data)) {
        return str_shuffle($data);
    }
    $data = traversableToArray($data);
    shuffle($data);

    return $data;
}

function template_function_slice($data, $start, $length, $preserveKeys = false)
{
    if (is_string($data)) {
        if ($length === null) {
            return substr($data, $start);
        }

        return substr($data, $start, $length);
    }
    $data = traversableToArray($data);

    return array_slice($data, $start, $length, $preserveKeys);
}

function template_function_sort($data, $reverse = false)
{
    $data = traversableToArray($data);
    if ($reverse) {
        arsort($data);
    } else {
        asort($data);
    }

    return $data;
}

function template_function_source(Environment $environment, $template)
{
    return $environment->getTemplateLoader()->getSource($template);
}

function template_function_spacify($string, $delimiter = ' ')
{
    if (!is_string($string)) {
        throw new InvalidArgumentException('Spacify expects a string.');
    }

    return implode($delimiter, str_split($string));
}

function template_function_split($string, $delimiter = '', $limit = null)
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

function template_function_truncate($string, $length, $ellipsis = '...')
{
    if (strlen($string) > $length) {
        $string = template_function_first($string, $length);
        $string .= $ellipsis;
    }

    return $string;
}

function template_function_urlEncode($data, $raw)
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

function template_function_without($data, $without)
{
    if (is_string($data)) {
        if (!is_string($without) && !is_array($without)) {
            if (!$without instanceof Traversable) {
                throw new InvalidArgumentException('Without expects string or array arguments.');
            }
            $without = iterator_to_array($without);
        }

        return str_replace($without, '', $data);
    }
    if ($data instanceof Traversable) {
        $data = iterator_to_array($data);
    } elseif (!is_array($data)) {
        throw new InvalidArgumentException('Without expects string or array arguments.');
    }
    if (!is_array($without)) {
        if ($without instanceof Traversable) {
            $without = iterator_to_array($without);
        } else {
            $without = array($without);
        }
    }

    return array_diff($data, $without);
}
