<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Operators\AndOperator;
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
use Modules\Templating\Compiler\Operators\MultiplicationOperator;
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

class TemplateDescriptor
{
    private $blocks;
    private $tags;
    private $operators;

    public function __construct()
    {
        $this->tags         = array(
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
        $this->operators    = array(
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
            new MultiplicationOperator(),
            new IncrementOperator(),
            new DecrementOperator(),
            new PlusOperator(),
            new MinusOperator(),
            new PowerOperator(),
            new RangeOperator(),
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
        $this->safe_filters = array();
    }

    public function addSafeFilter($filter)
    {
        return $this->safe_filters[] = $filter;
    }

    public function safeFilters()
    {
        return $this->safe_filters;
    }

    public function blocks()
    {
        return $this->blocks;
    }

    public function tags()
    {
        return $this->tags;
    }

    public function operators()
    {
        return $this->operators;
    }
}
