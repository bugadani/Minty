<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Minty\Extensions;

use Minty\Compiler\NodeVisitors\SafeOutputVisitor;
use Minty\Compiler\Operator;
use Minty\Compiler\Operators\ArithmeticOperators\AdditionOperator;
use Minty\Compiler\Operators\ArithmeticOperators\DivisionOperator;
use Minty\Compiler\Operators\ArithmeticOperators\ExponentialOperator;
use Minty\Compiler\Operators\ArithmeticOperators\MultiplicationOperator;
use Minty\Compiler\Operators\ArithmeticOperators\RemainderOperator;
use Minty\Compiler\Operators\ArithmeticOperators\SubtractionOperator;
use Minty\Compiler\Operators\BitwiseOperators\BitwiseAndOperator;
use Minty\Compiler\Operators\BitwiseOperators\BitwiseNotOperator;
use Minty\Compiler\Operators\BitwiseOperators\BitwiseOrOperator;
use Minty\Compiler\Operators\BitwiseOperators\BitwiseXorOperator;
use Minty\Compiler\Operators\BitwiseOperators\ShiftLeftOperator;
use Minty\Compiler\Operators\BitwiseOperators\ShiftRightOperator;
use Minty\Compiler\Operators\ComparisonOperators\EqualsOperator;
use Minty\Compiler\Operators\ComparisonOperators\GreaterThanOperator;
use Minty\Compiler\Operators\ComparisonOperators\GreaterThanOrEqualsOperator;
use Minty\Compiler\Operators\ComparisonOperators\IdenticalOperator;
use Minty\Compiler\Operators\ComparisonOperators\LessThanOperator;
use Minty\Compiler\Operators\ComparisonOperators\LessThanOrEqualsOperator;
use Minty\Compiler\Operators\ComparisonOperators\NotEqualsOperator;
use Minty\Compiler\Operators\ComparisonOperators\NotIdenticalOperator;
use Minty\Compiler\Operators\ConcatenationOperator;
use Minty\Compiler\Operators\ExclusiveRangeOperator;
use Minty\Compiler\Operators\ExistenceOperators\IsNotSetOperator;
use Minty\Compiler\Operators\ExistenceOperators\IsSetOperator;
use Minty\Compiler\Operators\FalseCoalescingOperator;
use Minty\Compiler\Operators\FilterOperator;
use Minty\Compiler\Operators\LogicOperators\AndOperator;
use Minty\Compiler\Operators\LogicOperators\NotOperator;
use Minty\Compiler\Operators\LogicOperators\OrOperator;
use Minty\Compiler\Operators\LogicOperators\XorOperator;
use Minty\Compiler\Operators\PropertyAccessOperator;
use Minty\Compiler\Operators\RangeOperator;
use Minty\Compiler\Operators\TestOperators\ContainsOperator;
use Minty\Compiler\Operators\TestOperators\DivisibleByOperator;
use Minty\Compiler\Operators\TestOperators\EndsOperator;
use Minty\Compiler\Operators\TestOperators\MatchesOperator;
use Minty\Compiler\Operators\TestOperators\NotContainsOperator;
use Minty\Compiler\Operators\TestOperators\NotDivisibleByOperator;
use Minty\Compiler\Operators\TestOperators\NotEndsOperator;
use Minty\Compiler\Operators\TestOperators\NotMatchesOperator;
use Minty\Compiler\Operators\TestOperators\NotStartsOperator;
use Minty\Compiler\Operators\TestOperators\StartsOperator;
use Minty\Compiler\Operators\UnaryOperators\EmptyOperator;
use Minty\Compiler\Operators\UnaryOperators\EvenOperator;
use Minty\Compiler\Operators\UnaryOperators\MinusOperator;
use Minty\Compiler\Operators\UnaryOperators\NotEmptyOperator;
use Minty\Compiler\Operators\UnaryOperators\OddOperator;
use Minty\Compiler\Operators\UnaryOperators\PlusOperator;
use Minty\Compiler\Operators\UnaryOperators\PostDecrementOperator;
use Minty\Compiler\Operators\UnaryOperators\PostIncrementOperator;
use Minty\Compiler\Operators\UnaryOperators\PreDecrementOperator;
use Minty\Compiler\Operators\UnaryOperators\PreIncrementOperator;
use Minty\Compiler\Tags\AutofilterTag;
use Minty\Compiler\Tags\CaptureTag;
use Minty\Compiler\Tags\CaseTag;
use Minty\Compiler\Tags\DoTag;
use Minty\Compiler\Tags\ElseIfTag;
use Minty\Compiler\Tags\ElseTag;
use Minty\Compiler\Tags\ForTag;
use Minty\Compiler\Tags\Helpers\MethodNodeHelper;
use Minty\Compiler\Tags\IfTag;
use Minty\Compiler\Tags\ListTag;
use Minty\Compiler\Tags\PrintTag;
use Minty\Compiler\Tags\SetTag;
use Minty\Compiler\Tags\SwitchTag;
use Minty\Compiler\Tags\TemplateExtension\BlockTag;
use Minty\Compiler\Tags\TemplateExtension\EmbedTag;
use Minty\Compiler\Tags\TemplateExtension\ExtendsTag;
use Minty\Compiler\Tags\TemplateExtension\IncludeTag;
use Minty\Compiler\Tags\TemplateExtension\ParentTag;
use Minty\Compiler\TemplateFunction;
use Minty\Context;
use Minty\Environment;
use Minty\Extension;

class Core extends Extension
{

    public function getExtensionName()
    {
        return 'core';
    }

    public function getBinaryOperators()
    {
        return array(
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
            new NotEndsOperator(8, Operator::NONE),
            new NotMatchesOperator(8, Operator::NONE),
            new NotStartsOperator(8, Operator::NONE),
            new StartsOperator(8, Operator::NONE),
            new DivisibleByOperator(8, Operator::NONE),
            new NotDivisibleByOperator(8, Operator::NONE),
            //other
            new FalseCoalescingOperator(1),
            new ConcatenationOperator(10),
            new PropertyAccessOperator(16),
            new FilterOperator(11),
            new RangeOperator(9),
            new ExclusiveRangeOperator(9),
        );
    }

    public function getPrefixUnaryOperators()
    {
        return array(
            new PreDecrementOperator(13, Operator::RIGHT),
            new PreIncrementOperator(13, Operator::RIGHT),
            new BitwiseNotOperator(13, Operator::RIGHT),
            new MinusOperator(13, Operator::RIGHT),
            new PlusOperator(13, Operator::RIGHT),
            new NotOperator(12, Operator::RIGHT)
        );
    }

    public function getPostfixUnaryOperators()
    {
        return array(
            new IsSetOperator(15, Operator::RIGHT),
            new IsNotSetOperator(15, Operator::RIGHT),
            new EvenOperator(15, Operator::NONE),
            new OddOperator(15, Operator::NONE),
            new PostDecrementOperator(15),
            new PostIncrementOperator(15),
            new EmptyOperator(15),
            new NotEmptyOperator(15),
        );
    }

    public function getTags()
    {
        $methodHelper = new MethodNodeHelper();

        return array(
            new AutofilterTag(),
            new BlockTag($methodHelper),
            new CaptureTag(),
            new CaseTag(),
            new DoTag(),
            new ElseIfTag(),
            new ElseTag(),
            new EmbedTag(),
            new ExtendsTag(),
            new ForTag(),
            new IfTag(),
            new IncludeTag($methodHelper),
            new ListTag($methodHelper),
            new ParentTag($methodHelper),
            new PrintTag(),
            new SetTag(),
            new SwitchTag()
        );
    }

    public function getNodeVisitors()
    {
        return array(
            new SafeOutputVisitor()
        );
    }

    public function getFunctions()
    {
        $namespace = '\\' . __NAMESPACE__;

        return array(
            new TemplateFunction('abs'),
            new TemplateFunction('attributes', $namespace . '\template_function_attributes', array(
                'is_safe' => array(
                    'html',
                    'xml'
                )
            )),
            new TemplateFunction('batch', $namespace . '\template_function_batch'),
            new TemplateFunction('capitalize', 'ucfirst'),
            new TemplateFunction('count', null, array('is_safe' => true)),
            new TemplateFunction('cycle', $namespace . '\template_function_cycle'),
            new TemplateFunction('date_format', $namespace . '\template_function_dateFormat'),
            new TemplateFunction('default', null, array('compiler' => '\Minty\Extensions\Compilers\DefaultCompiler')),
            new TemplateFunction('divisible', $namespace . '\template_function_divisible'),
            new TemplateFunction('extract', $namespace . '\template_function_extract', array('needs_context' => true)),
            new TemplateFunction('empty', $namespace . '\template_function_empty'),
            new TemplateFunction('ends', $namespace . '\template_function_ends'),
            new TemplateFunction('filter', $namespace . '\template_function_filter', array(
                'is_safe'           => true,
                'needs_environment' => true
            )),
            new TemplateFunction('filter_html', 'htmlspecialchars', array(
                'is_safe' => array(
                    'xml',
                    'html'
                )
            )),
            new TemplateFunction('filter_json', 'json_encode', array('is_safe' => 'json')),
            new TemplateFunction('first', $namespace . '\template_function_first'),
            new TemplateFunction('format', 'sprintf'),
            new TemplateFunction('in', $namespace . '\template_function_in'),
            new TemplateFunction('is_int'),
            new TemplateFunction('is_numeric'),
            new TemplateFunction('is_string'),
            new TemplateFunction('join', $namespace . '\template_function_join'),
            new TemplateFunction('json_encode'),
            new TemplateFunction('keys', 'array_keys'),
            new TemplateFunction('last', $namespace . '\template_function_last'),
            new TemplateFunction('length', $namespace . '\template_function_length', array('is_safe' => true)),
            new TemplateFunction('link_to', $namespace . '\template_function_linkTo', array(
                'is_safe' => array(
                    'html',
                    'xml'
                )
            )),
            new TemplateFunction('lower', 'strtolower'),
            new TemplateFunction('ltrim'),
            new TemplateFunction('match', $namespace . '\template_function_match'),
            new TemplateFunction('max'),
            new TemplateFunction('merge', 'array_merge'),
            new TemplateFunction('min'),
            new TemplateFunction('nl2br', null, array('is_safe' => 'html')),
            new TemplateFunction('number_format', null, array('is_safe' => true)),
            new TemplateFunction('pluck', $namespace . '\template_function_pluck'),
            new TemplateFunction('pow', null, array('is_safe' => true)),
            new TemplateFunction('random', $namespace . '\template_function_random'),
            new TemplateFunction('range', null, array('is_safe' => true)),
            new TemplateFunction('raw', $namespace . '\template_function_raw', array('is_safe' => true)),
            new TemplateFunction('regexp_replace', $namespace . '\template_function_regexpReplace'),
            new TemplateFunction('replace', $namespace . '\template_function_replace'),
            new TemplateFunction('reverse', $namespace . '\template_function_reverse'),
            new TemplateFunction('rtrim'),
            new TemplateFunction('shuffle', $namespace . '\template_function_shuffle'),
            new TemplateFunction('slice', $namespace . '\template_function_slice'),
            new TemplateFunction('sort', $namespace . '\template_function_sort'),
            new TemplateFunction('source', $namespace . '\template_function_source', array('needs_environment' => true)),
            new TemplateFunction('spacify', $namespace . '\template_function_spacify'),
            new TemplateFunction('split', $namespace . '\template_function_split'),
            new TemplateFunction('starts', $namespace . '\template_function_starts'),
            new TemplateFunction('striptags', 'strip_tags', array('is_safe' => true)),
            new TemplateFunction('title_case', 'ucwords'),
            new TemplateFunction('trim'),
            new TemplateFunction('truncate', $namespace . '\template_function_truncate'),
            new TemplateFunction('upper', 'strtoupper'),
            new TemplateFunction('url_encode', $namespace . '\template_function_urlEncode'),
            new TemplateFunction('without', $namespace . '\template_function_without'),
            new TemplateFunction('wordwrap')
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
    if ($data instanceof \Traversable) {
        return iterator_to_array($data);
    }
    if (is_array($data)) {
        return $data;
    } elseif (method_exists($data, 'toArray')) {
        $data = $data->toArray();
        if (is_array($data)) {
            return $data;
        }
    }
    throw new \InvalidArgumentException('Expected an array or traversable object.');
}

/* Template functions */

/* Template functions */

function template_function_attributes(array $args)
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

function template_function_divisible($data, $num)
{
    $div = $data / $num;

    return $div === (int) $div;
}

function template_function_empty($data)
{
    return empty($data);
}

function template_function_ends($data, $str)
{
    return substr($data, strlen($data) - strlen($str)) === $str;
}

function template_function_extract(Context $context, $source, $keys)
{
    foreach ((array) $keys as $key) {
        $context->$key = $context->getProperty($source, $key);
    }
}

function template_function_filter(Environment $environment, $data, $for = 'html')
{
    if (!is_string($data)) {
        return $data;
    }
    if (!$environment->hasFunction('filter_' . $for)) {
        $for = $environment->getOption('default_autofilter_strategy');
    }

    return $environment->getFunction('filter_' . $for)->call($data);
}

function template_function_first($data, $number = 1)
{
    return template_function_slice($data, 0, $number);
}

function template_function_in($needle, $haystack)
{
    if (is_string($haystack)) {
        return strpos($haystack, $needle) !== false;
    }
    if ($haystack instanceof \Traversable) {
        $haystack = iterator_to_array($haystack);
    } elseif (!is_array($haystack)) {
        throw new \InvalidArgumentException('The in keyword expects an array, a string or a Traversable instance');
    }

    return in_array($needle, $haystack);
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
    if (is_array($data) || $data instanceof \Countable) {
        return count($data);
    }
    throw new \InvalidArgumentException('Reverse expects an array, a string or a Countable instance');
}

function template_function_linkTo($label, $url, array $attrs = array())
{
    $attrs['href'] = $url;

    $attributes = template_function_attributes($attrs);

    return "<a{$attributes}>{$label}</a>";
}

function template_function_match($string, $pattern)
{
    return preg_match($pattern, $string);
}

function template_function_pluck($array, $key)
{
    if (!is_array($array) && !$array instanceof \Traversable) {
        throw new \InvalidArgumentException('Pluck expects a two-dimensional array as the first argument.');
    }
    $return = array();

    foreach ($array as $element) {
        if (is_array($element) || $element instanceof \ArrayAccess) {
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

function template_function_slice($data, $start, $length = null, $preserveKeys = false)
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
        rsort($data);
    } else {
        sort($data);
    }

    return $data;
}

function template_function_source(Environment $environment, $template)
{
    return $environment->getSource(
        $environment->findFirstExistingTemplate($template)
    );
}

function template_function_spacify($string, $delimiter = ' ')
{
    if (!is_string($string)) {
        throw new \InvalidArgumentException('Spacify expects a string.');
    }

    return implode($delimiter, str_split($string));
}

function template_function_split($string, $delimiter = '', $limit = null)
{
    if (!is_string($string)) {
        throw new \InvalidArgumentException('Split expects a string');
    }
    if ($delimiter === '') {
        return str_split($string, $limit ? : 1);
    }
    if ($limit === null) {
        return explode($delimiter, $string);
    }

    return array_slice(explode($delimiter, $string, $limit + 1), 0, $limit);
}

function template_function_starts($data, $str)
{
    return strpos($data, $str) === 0;
}

function template_function_truncate($string, $length, $ellipsis = '...')
{
    if (strlen($string) > $length) {
        $string = substr($string, 0, $length);
        $string .= $ellipsis;
    }

    return $string;
}

function template_function_urlEncode($data, $raw = false)
{
    if ($data instanceof \Traversable) {
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
            if (!$without instanceof \Traversable) {
                throw new \InvalidArgumentException('Without expects string or array arguments.');
            }
            $without = iterator_to_array($without);
        }

        return str_replace($without, '', $data);
    }
    if ($data instanceof \Traversable) {
        $data = iterator_to_array($data);
    } elseif (!is_array($data)) {
        throw new \InvalidArgumentException('Without expects string or array arguments.');
    }
    if (!is_array($without)) {
        if ($without instanceof \Traversable) {
            $without = iterator_to_array($without);
        } else {
            $without = array($without);
        }
    }

    return array_diff($data, $without);
}
