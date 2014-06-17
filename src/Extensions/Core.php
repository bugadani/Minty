<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Extensions;

use InvalidArgumentException;
use Modules\Templating\Compiler\NodeVisitors\SafeOutputVisitor;
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
use Modules\Templating\Compiler\Tags\AutofilterTag;
use Modules\Templating\Compiler\Tags\CaptureTag;
use Modules\Templating\Compiler\Tags\CaseTag;
use Modules\Templating\Compiler\Tags\DoTag;
use Modules\Templating\Compiler\Tags\ElseIfTag;
use Modules\Templating\Compiler\Tags\ElseTag;
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
        return array(
            new AutofilterTag(),
            new BlockTag(),
            new CaptureTag(),
            new CaseTag(),
            new DoTag(),
            new ElseIfTag(),
            new ElseTag(),
            new EmbedTag(),
            new ExtendsTag(),
            new ForTag(),
            new IfTag(),
            new IncludeTag(),
            new ListTag(),
            new ParentTag(),
            new PrintTag(),
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
            new TemplateFunction('default', null, array('compiler' => '\Modules\Templating\Extensions\Compilers\DefaultCompiler')),
            new TemplateFunction('divisible', $namespace . '\template_function_divisible'),
            new TemplateFunction('empty', $namespace . '\template_function_empty'),
            new TemplateFunction('ends', $namespace . '\template_function_ends'),
            new TemplateFunction('filter', $namespace . '\template_function_filter', array('is_safe' => true)),
            new TemplateFunction('in', $namespace . '\template_function_in'),
            new TemplateFunction('match', $namespace . '\template_function_match'),
            new TemplateFunction('pow', null, array('is_safe' => true)),
            new TemplateFunction('range', null, array('is_safe' => true)),
            new TemplateFunction('raw', $namespace . '\template_function_raw', array('is_safe' => true)),
            new TemplateFunction('starts', $namespace . '\template_function_starts')
        );
    }
}

/* Template functions */

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

function template_function_filter($data, $for = 'html')
{
    if (!is_string($data)) {
        return $data;
    }
    switch ($for) {
        default:
        case 'html':
            return htmlspecialchars($data);

        case 'json':
            return json_encode($data);
    }
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

function template_function_match($string, $pattern)
{
    return preg_match($pattern, $string);
}

function template_function_raw($data)
{
    return $data;
}

function template_function_starts($data, $str)
{
    return strpos($data, $str) === 0;
}
