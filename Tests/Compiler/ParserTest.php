<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Modules\Templating\Compiler\Environment;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\SyntaxException;
use Modules\Templating\Compiler\Token;
use PHPUnit_Framework_TestCase;

require_once __DIR__ . '/../../TemplatingOptions.php';
require_once __DIR__ . '/../../Compiler/Token.php';
require_once __DIR__ . '/../../Compiler/TokenStream.php';
require_once __DIR__ . '/../../Compiler/Environment.php';
require_once __DIR__ . '/../../Compiler/Parser.php';
require_once __DIR__ . '/../../Compiler/CompileException.php';
require_once __DIR__ . '/../../Compiler/SyntaxException.php';
require_once __DIR__ . '/../../Compiler/Tag.php';
require_once __DIR__ . '/../../Compiler/Tags/Block.php';
require_once __DIR__ . '/../../Compiler/Tags/ParentTag.php';
require_once __DIR__ . '/../../Compiler/Tags/ListTag.php';
require_once __DIR__ . '/../../Compiler/Tags/CaseTag.php';
require_once __DIR__ . '/../../Compiler/Tags/ElseTag.php';
require_once __DIR__ . '/../../Compiler/Tags/ElseIfTag.php';
require_once __DIR__ . '/../../Compiler/Tags/ExtendsTag.php';
require_once __DIR__ . '/../../Compiler/Tags/Blocks/IfBlock.php';
require_once __DIR__ . '/../../Compiler/Tags/Blocks/ForBlock.php';
require_once __DIR__ . '/../../Compiler/Tags/Blocks/BlockBlock.php';
require_once __DIR__ . '/../../Compiler/Tags/Blocks/TemplateBlock.php';
require_once __DIR__ . '/../../Compiler/Tags/Blocks/SwitchBlock.php';

require_once __DIR__ . '/../../Extension.php';
require_once __DIR__ . '/../../Compiler/Extensions/Core.php';

require_once __DIR__ . '/../../Compiler/TemplateFunction.php';
require_once __DIR__ . '/../../Compiler/Functions/SimpleFunction.php';
require_once __DIR__ . '/../../Compiler/Functions/MethodFunction.php';
require_once __DIR__ . '/../../Compiler/Functions/CallbackFunction.php';

require_once __DIR__ . '/../../Compiler/Operator.php';
require_once __DIR__ . '/../../Compiler/Operators/ComparisonOperators/EqualOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/ComparisonOperators/GreaterOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/ComparisonOperators/GreaterOrEqualOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/ComparisonOperators/LessOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/ComparisonOperators/LessOrEqualOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/ComparisonOperators/NotEqualOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/TestOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/TestOperators/DivisibleByOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/TestOperators/EmptyOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/TestOperators/EvenOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/TestOperators/LikeOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/TestOperators/OddOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/TestOperators/SameAsOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/IsOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/StartsWithOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/EndsWithOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/MatchesOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/AndOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/OrOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/ShiftLeftOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/ShiftRightOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/ArrowOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/ColonOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/CommaOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/ConcatenationOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/DoubleArrowOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/MinusOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/RangeOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/MultiplicationOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/ParenthesisOperators.php';
require_once __DIR__ . '/../../Compiler/Operators/ParenthesisOperators/BracketOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/ParenthesisOperators/ParenthesisOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/PeriodOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/PipeOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/PlusOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/PowerOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/OrOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/StringOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/UnaryOperators.php';
require_once __DIR__ . '/../../Compiler/Operators/UnaryOperators/DecrementOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/UnaryOperators/IncrementOperator.php';
require_once __DIR__ . '/../../Compiler/Operators/UnaryOperators/NotOperator.php';

class ParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Parser
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Parser(new Environment(new TemplatingOptions()));
    }

    public function testParseEmpty()
    {
        $stream = $this->object->parse('');
        $empty  = array(new Token(Token::EOF, null, 1));
        $this->assertEquals($empty, $stream->getTokens());
    }

    public function tagsProvider()
    {
        return array(
            0  => array(
                'something',
                array(
                    new Token(Token::TEXT, 'something'),
                    new Token(Token::EOF)
                )
            ),
            1  => array(
                'something{a tag}',
                array(
                    new Token(Token::TEXT, 'something'),
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::IDENTIFIER, 'a tag'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::EOF)
                )
            ),
            2  => array(
                "testing { whitespaces }, \n {multiple} tags and \n {newline}",
                array(
                    new Token(Token::TEXT, 'testing ', 1),
                    new Token(Token::EXPRESSION_START, null, 1),
                    new Token(Token::IDENTIFIER, 'whitespaces', 1),
                    new Token(Token::EXPRESSION_END, null, 1),
                    new Token(Token::TEXT, ", \n ", 1),
                    new Token(Token::EXPRESSION_START, null, 2),
                    new Token(Token::IDENTIFIER, 'multiple', 2),
                    new Token(Token::EXPRESSION_END, null, 2),
                    new Token(Token::TEXT, " tags and \n ", 2),
                    new Token(Token::EXPRESSION_START, null, 3),
                    new Token(Token::IDENTIFIER, 'newline', 3),
                    new Token(Token::EXPRESSION_END, null, 3),
                    new Token(Token::EOF)
                )
            ),
            3  => array(
                '{1}',
                array(
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::LITERAL, '1'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::EOF)
                )
            ),
            4  => array(
                '{1+2}',
                array(
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::LITERAL, '1'),
                    new Token(Token::OPERATOR, '+'),
                    new Token(Token::LITERAL, '2'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::EOF)
                )
            ),
            5  => array(
                '{a: 1+2}',
                array(
                    new Token(Token::KEYWORD, 'assign'),
                    new Token(Token::IDENTIFIER, 'a'),
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::LITERAL, '1'),
                    new Token(Token::OPERATOR, '+'),
                    new Token(Token::LITERAL, '2'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::EOF)
                )
            ),
            6  => array(
                '{1+(2+3)}',
                array(
                    new Token(Token::EXPRESSION_START, '{'),
                    new Token(Token::LITERAL, '1'),
                    new Token(Token::OPERATOR, '+'),
                    new Token(Token::EXPRESSION_START, '('),
                    new Token(Token::LITERAL, '2'),
                    new Token(Token::OPERATOR, '+'),
                    new Token(Token::LITERAL, '3'),
                    new Token(Token::EXPRESSION_END, ')'),
                    new Token(Token::EXPRESSION_END, '}'),
                    new Token(Token::EOF)
                )
            ),
            7  => array(
                '{+(1+2)+3}',
                array(
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::OPERATOR, '+'),
                    new Token(Token::EXPRESSION_START, '('),
                    new Token(Token::LITERAL, '1'),
                    new Token(Token::OPERATOR, '+'),
                    new Token(Token::LITERAL, '2'),
                    new Token(Token::EXPRESSION_END, ')'),
                    new Token(Token::OPERATOR, '+'),
                    new Token(Token::LITERAL, '3'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::EOF)
                )
            ),
            8  => array(
                '{"string"}',
                array(
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::STRING, 'string'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::EOF)
                )
            ),
            9  => array(
                '{"1+(2+3)"}',
                array(
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::STRING, '1+(2+3)'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::EOF)
                )
            ),
            10 => array(
                '{"1+{2+3}"}',
                array(
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::STRING, '1+{2+3}'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::EOF)
                )
            ),
            11 => array(
                '{func(2)}',
                array(
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::IDENTIFIER, 'func'),
                    new Token(Token::ARGUMENT_LIST_START, 'args'),
                    new Token(Token::LITERAL, '2'),
                    new Token(Token::ARGUMENT_LIST_END, 'args'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::EOF)
                )
            ),
            12 => array(
                '{func(2, 3)}',
                array(
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::IDENTIFIER, 'func'),
                    new Token(Token::ARGUMENT_LIST_START, 'args'),
                    new Token(Token::LITERAL, '2'),
                    new Token(Token::OPERATOR, ','),
                    new Token(Token::LITERAL, '3'),
                    new Token(Token::ARGUMENT_LIST_END, 'args'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::EOF)
                )
            ),
            13 => array(
                '{func(2+3, 3)}',
                array(
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::IDENTIFIER, 'func'),
                    new Token(Token::ARGUMENT_LIST_START, 'args'),
                    new Token(Token::LITERAL, '2'),
                    new Token(Token::OPERATOR, '+'),
                    new Token(Token::LITERAL, '3'),
                    new Token(Token::OPERATOR, ','),
                    new Token(Token::LITERAL, '3'),
                    new Token(Token::ARGUMENT_LIST_END, 'args'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::EOF)
                )
            ),
            14 => array(
                '{func(2+3, other("string"))}',
                array(
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::IDENTIFIER, 'func'),
                    new Token(Token::ARGUMENT_LIST_START, 'args'),
                    new Token(Token::LITERAL, '2'),
                    new Token(Token::OPERATOR, '+'),
                    new Token(Token::LITERAL, '3'),
                    new Token(Token::OPERATOR, ','),
                    new Token(Token::IDENTIFIER, 'other'),
                    new Token(Token::ARGUMENT_LIST_START, 'args'),
                    new Token(Token::STRING, 'string'),
                    new Token(Token::ARGUMENT_LIST_END, 'args'),
                    new Token(Token::ARGUMENT_LIST_END, 'args'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::EOF)
                )
            ),
            15 => array(
                '{identifier[a]}',
                array(
                    new Token(Token::EXPRESSION_START, '{'),
                    new Token(Token::IDENTIFIER, 'identifier'),
                    new Token(Token::OPERATOR, '['),
                    new Token(Token::IDENTIFIER, 'a'),
                    new Token(Token::OPERATOR, ']'),
                    new Token(Token::EXPRESSION_END, '}'),
                    new Token(Token::EOF)
                )
            ),
            16 => array(
                '{a: [a]}',
                array(
                    new Token(Token::KEYWORD, 'assign'),
                    new Token(Token::IDENTIFIER, 'a'),
                    new Token(Token::EXPRESSION_START, '{'),
                    new Token(Token::ARGUMENT_LIST_START, 'array'),
                    new Token(Token::IDENTIFIER, 'a'),
                    new Token(Token::ARGUMENT_LIST_END, 'array'),
                    new Token(Token::EXPRESSION_END, '}'),
                    new Token(Token::EOF)
                )
            ),
            17 => array(
                '{-4.56}',
                array(
                    new Token(Token::EXPRESSION_START, '{'),
                    new Token(Token::OPERATOR, '-'),
                    new Token(Token::LITERAL, 4.56),
                    new Token(Token::EXPRESSION_END, '}'),
                    new Token(Token::EOF)
                )
            ),
            18 => array(
                '{identifier2.56}',
                array(
                    new Token(Token::EXPRESSION_START, '{'),
                    new Token(Token::IDENTIFIER, 'identifier2'),
                    new Token(Token::OPERATOR, '.'),
                    new Token(Token::LITERAL, '56'),
                    new Token(Token::EXPRESSION_END, '}'),
                    new Token(Token::EOF)
                )
            ),
            19 => array(
                '{for i in []}{endfor}',
                array(
                    new Token(Token::BLOCK_START, 'for'),
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::IDENTIFIER, 'i'),
                    new Token(Token::KEYWORD, 'in'),
                    new Token(Token::ARGUMENT_LIST_START, 'array'),
                    new Token(Token::ARGUMENT_LIST_END, 'array'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::BLOCK_END, 'for'),
                    new Token(Token::EOF)
                )
            ),
            20 => array(
                '{for i in [a, b]}{endfor}',
                array(
                    new Token(Token::BLOCK_START, 'for'),
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::IDENTIFIER, 'i'),
                    new Token(Token::KEYWORD, 'in'),
                    new Token(Token::ARGUMENT_LIST_START, 'array'),
                    new Token(Token::IDENTIFIER, 'a'),
                    new Token(Token::OPERATOR, ','),
                    new Token(Token::IDENTIFIER, 'b'),
                    new Token(Token::ARGUMENT_LIST_END, 'array'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::BLOCK_END, 'for'),
                    new Token(Token::EOF)
                )
            ),
            21 => array(
                '{variable|filter1|filter2}',
                array(
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::IDENTIFIER, 'variable'),
                    new Token(Token::OPERATOR, '|'),
                    new Token(Token::IDENTIFIER, 'filter1'),
                    new Token(Token::OPERATOR, '|'),
                    new Token(Token::IDENTIFIER, 'filter2'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::EOF)
                )
            ),
            22 => array(
                '{if a}{else}{endif}',
                array(
                    new Token(Token::BLOCK_START, 'if'),
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::IDENTIFIER, 'a'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::TAG, 'else'),
                    new Token(Token::BLOCK_END, 'if'),
                    new Token(Token::EOF)
                )
            ),
            23 => array(
                '{var[1][2]}',
                array(
                    new Token(Token::EXPRESSION_START, '{'),
                    new Token(Token::IDENTIFIER, 'var'),
                    new Token(Token::OPERATOR, '['),
                    new Token(Token::LITERAL, '1'),
                    new Token(Token::OPERATOR, ']'),
                    new Token(Token::OPERATOR, '['),
                    new Token(Token::LITERAL, '2'),
                    new Token(Token::OPERATOR, ']'),
                    new Token(Token::EXPRESSION_END, '}'),
                    new Token(Token::EOF)
                )
            ),
            24 => array(
                '{var^func()}',
                array(
                    new Token(Token::EXPRESSION_START, '{'),
                    new Token(Token::IDENTIFIER, 'var'),
                    new Token(Token::OPERATOR, '^'),
                    new Token(Token::IDENTIFIER, 'func'),
                    new Token(Token::ARGUMENT_LIST_START, 'args'),
                    new Token(Token::ARGUMENT_LIST_END, 'args'),
                    new Token(Token::EXPRESSION_END, '}'),
                    new Token(Token::EOF)
                )
            ),
            25 => array(
                '{a: ["key": 2]}',
                array(
                    new Token(Token::KEYWORD, 'assign'),
                    new Token(Token::IDENTIFIER, 'a'),
                    new Token(Token::EXPRESSION_START, '{'),
                    new Token(Token::ARGUMENT_LIST_START, 'array'),
                    new Token(Token::STRING, 'key'),
                    new Token(Token::OPERATOR, '=>'),
                    new Token(Token::LITERAL, '2'),
                    new Token(Token::ARGUMENT_LIST_END, 'array'),
                    new Token(Token::EXPRESSION_END, '}'),
                    new Token(Token::EOF)
                )
            ),
            26 => array(
                '{if set a}{endif}',
                array(
                    new Token(Token::BLOCK_START, 'if'),
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::KEYWORD, 'set'),
                    new Token(Token::IDENTIFIER, 'a'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::BLOCK_END, 'if'),
                    new Token(Token::EOF)
                )
            ),
            27 => array(
                '{if 0 || 1}{endif}',
                array(
                    new Token(Token::BLOCK_START, 'if'),
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::LITERAL, 0),
                    new Token(Token::OPERATOR, '||'),
                    new Token(Token::LITERAL, 1),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::BLOCK_END, 'if'),
                    new Token(Token::EOF)
                )
            ),
            28 => array(
                '{aset }',
                array(
                    new Token(Token::EXPRESSION_START),
                    new Token(Token::IDENTIFIER, 'aset'),
                    new Token(Token::EXPRESSION_END),
                    new Token(Token::EOF)
                )
            ),
        );
    }

    /**
     * @dataProvider tagsProvider
     */
    public function testTags($template, $expected)
    {
        $actual = $this->object->parse($template);
        foreach ($actual->getTokens() as $k => $token) {
            $this->assertEquals($expected[$k]->getTypeString(), $token->getTypeString(), $k);
            if ($expected[$k]->getValue() !== null) {
                $this->assertEquals($expected[$k]->getValue(), $token->getValue(), $k);
            }
            if ($expected[$k]->getLine() !== 0) {
                $this->assertEquals($expected[$k]->getLine(), $token->getLine(), $k);
            }
        }
    }

    public function syntaxExceptionProvider()
    {
        return array(
            array('{"}', true), /* 0 */
            array('{"\'}', true),
            array('{(}', true),
            array('{)}', true),
            array('{()}', true),
            array('{.}', true),
            array('{ident.}', true),
            array('{ident.key}', false),
            array('{ident4.2}', false),
            array('{ident4.2+}', true),
            array('{ident4.2-}', true), /* 10 */
            array('{function()}', false),
            array('{expression(with)+other(brackets)}', false),
            array('{expression(with)+other(unbalanced brackets}', true),
            array('{[a]}', false),
            array('{2[a]}', true),
            array('{ident[a,b]}', true),
            array('{ident[a+]}', true),
            array('{ident[a+1]}', false),
            array('{ident->()}', true),
            array('{var^}', true), /* 20 */
            array('{[,ident]}', true),
            array('{var^-2}', false),
            array('{ident(function(), object->method(param1["string key"], "param2"))}', false),
            array('{ident(function(), object->method(param1["string key"], "param2"), [a1, a2])}', false),
            array('{var.1.2}', false),
            array('{var|filter(arg)}', false),
            //If syntax
            array('{if expr}', true),
            array('{endif}', true),
            array('{else}', true),
            array('{if expr}{endif}', false), /* 30 */
            array('{if expr}{if expr}{endif}', true),
            array('{if expr}{if expr}{endif}{endif}', false),
            array('{if expr}{raw}{endif}', true),
            array('{if expr}{raw}{endraw}{endif}', false),
            array('{if something}{elseif}{endif}', true),
            array('{if something}{elseif something}{endif}', false),
            array('{if i in something}{endif}', false),
            array('{if i[k] in something}{endif}', false),
            //For syntax
            array('{for|filter}{endfor}', true),
            array('{for a1}{endfor}', true), /* 40 */
            array('{for key => value in something}{endfor}', false),
            array('{for key something}{endfor}', true),
            array('{for i in []}{endfor}', false),
            array('{for i in [a1, a2]}{endfor}', false),
            array('{for key =>}{endfor}', true),
            array('{for key => value}{endfor}', true),
            array('{for key => value something}{endfor}', true),
            array('{for key => value in 5}{endfor}', true),
            array('{for key => value in []}{endfor}', false),
            array('{for key => value in variable}{endfor}', false), /* 50 */
            array('{for key => value in function()}{endfor}', false),
            array('{for key => value in []}{if expr}{endif}{endfor}', false),
            array('{for key => value in []}{else}{endfor}', false),
            array('{for i in[]}{endfor}', false),
            //++, -- operators
            array('{a++}', false),
            array('{a--}', false),
            array('{--a}', false),
            array('{++a}', false),
            array('{++1}', true),
            array('{1++}', true), /* 60 */
            array('{func()++}', true),
            array('{++func()}', true),
            //switch syntax
            array('{switch ident} {case}{endswitch}', true),
            array('{switch ident} {case 2}{else}{endswitch}', false),
            array('{switch ident} {endswitch}', true),
            array('{switch ident} text {else}{endswitch}', true),
            array('{switch} {else}{endswitch}', true),
            array('{switch ident} {else}{endswitch}', false),
            array('{switch ident}{if}{else}{endswitch}', true),
            array('{switch ident}{if}{else}{endif}{endswitch}', true), /* 70 */
            array('{switch ident}{case a}{if a}{else}{endif}{endswitch}', false),
            //Assign syntax
            array('{a: []}', false),
            array('{a|filter: []}', true),
            array('{a(): []}', true),
            array('{a: [function(), object->method(param1["string key"], "param2"), [a1, a2]]}', false),
            array('{a: function()}', false),
            array('{a: ident->function()}', false),
            array('{a: 2}', false),
            array('{a: "string"}', false),
            array('{a: "string" 2}', true), /* 80 */
            array('{extends "string"}', false),
            array('{extends string}', true),
            array('{extends 2}', true),
            array('{extends}', true),
            array('{a+}', true),
            array('{+"a"}', true),
            array('{func()+}', true),
            array('{func()->prop}', false),
            array('{func()->func()}', false),
            array('{var.2->func()}', true), /* 90 */
            array('{2->ident}', true),
            array('{var[2]->ident}', false),
            array('{a: ["key": 2, "c"]}', false),
            array('{a: [[1, 2]]}', false),
            array('{if set a}{endif}', false),
            array('{if set a()}{endif}', true),
            array('{if set a[]}{endif}', true),
            array('{if set a[2]}{endif}', false),
            array('{a[2]}', false),
            array('{a[-2]}', false), /* 100 */
            array('{a[-"2"]}', true),
            array('{true: 2}', true),
            array('{this->should_not("be: an assignment")}', false),
            array('{1..3}', false),
            array('{a..3}', false),
            array('{a..b}', false),
            array('{a()..b()}', false),
            array('{raw}{if true}{endraw}', false),
            array('{for i in []}{else}{endfor}', false),
            array('{"{"}', false),
            array('{\'{\'}', false),
        );
    }

    /**
     * @dataProvider syntaxExceptionProvider
     */
    public function testSyntaxExceptions($template, $throw)
    {
        try {
            $this->object->parse($template);
            if ($throw) {
                $this->fail('Parse should throw a SyntaxException. Parsed tokens: ' . print_r($this->object->getTokenStream(),
                                1));
            }
        } catch (SyntaxException $e) {
            if (!$throw) {
                $this->fail('Parse should not throw a SyntaxException. Exception: ' . $e->getMessage());
            }
        }
    }

    public function testTextTokenMerge()
    {
        $template = 'text{raw} this should be merged}{endraw}';
        $expected = array(
            new Token(Token::TEXT, 'text this should be merged}', 1),
            new Token(Token::EOF, null, 1)
        );
        $this->assertEquals($expected, $this->object->parse($template)->getTokens());
    }
}
