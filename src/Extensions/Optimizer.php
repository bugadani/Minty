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
use Modules\Templating\Compiler\Functions\MethodFunction;
use Modules\Templating\Compiler\Functions\SimpleFunction;
use Modules\Templating\Compiler\NodeVisitor;
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
use Modules\Templating\Compiler\NodeVisitors\ForLoopOptimizer;
use Modules\Templating\Compiler\Tags\AssignTag;
use Modules\Templating\Compiler\Tags\CaseTag;
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
use Modules\Templating\Extension;
use Traversable;

class Optimizer extends Extension
{

    public function getExtensionName()
    {
        return 'optimizer';
    }

    public function getNodeVisitors()
    {
        $optimizers = array(
            new ForLoopOptimizer()
        );

        return $optimizers;
    }
}
