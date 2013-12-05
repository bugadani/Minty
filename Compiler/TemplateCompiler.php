<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use BadMethodCallException;
use Modules\Templating\Compiler\Functions\MethodFunction;
use Modules\Templating\Compiler\Functions\SimpleFunction;
use Modules\Templating\Compiler\Tags\Block;
use Modules\Templating\TemplatingOptions;
use OutOfBoundsException;

class TemplateCompiler
{
    const STATE_TEXT                  = 0;
    const STATE_OUTPUT_EXPRESSION     = 1;
    const STATE_BLOCK_IF              = 2;
    const STATE_BLOCK_FOR             = 3;
    const STATE_BLOCK_SWITCH          = 4;
    const STATE_BLOCK_SWITCH_HAS_CASE = 5;
    const STATE_BLOCK_TEMPLATE        = 6;

    private static $main_template = 'Template';
    private static $operators     = array(
        '^'                   => 'compilePowerOperator',
        '->'                  => 'compileArrowOperator',
        '~'                   => 'compileTildeOperator',
        '|'                   => 'compileFilterOperator',
        '.'                   => 'compilePeriodOperator',
        '..'                  => 'compileRangeOperator',
        '...'                 => 'compileExclusiveRangeOperator',
        '['                   => 'compileOpeningBracketOperator',
        ']'                   => 'compileClosingBracketOperator',
        'is'                  => 'compileIsOperator',
        'starts with'         => 'compileStartsWithOperator',
        'ends with'           => 'compileEndsWithOperator',
        'matches'             => 'compileMatchesOperator',
        'does not start with' => 'compileNotStartsWithOperator',
        'does not end with'   => 'compileNotEndsWithOperator',
        'does not match'      => 'compileNotMatchesOperator',
        '&&'                  => ' && ',
        'and'                 => ' && ',
        '||'                  => ' || ',
        '*'                   => ' * ',
        '='                   => ' == ',
        ','                   => ', ',
        '=>'                  => ' => '
    );
    private static $keywords      = array(
        'set'    => 'compileSetKeyword',
        'in'     => 'compileInKeyword',
        'not in' => 'compileNotInKeyword',
    );

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var TemplatingOptions
     */
    private $options;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var TokenStream
     */
    private $tokens;
    private $tags;
    private $states;
    private $output;
    private $output_stack;
    private $templates;
    private $template_stack;
    private $extended_template;
    private $indentation = 0;
    private $filters;
    private $last_filter_safe;

    public function __construct(Environment $environment)
    {
        $this->parser      = new Parser($environment);
        $this->options     = $environment->getOptions();
        $this->environment = $environment;
        $this->filters     = $environment->getFunctions();

        foreach ($environment->tags() as $tag) {
            $name              = $tag->getTag();
            $this->tags[$name] = $tag;
        }
    }

    private function processArgumentList(Token $token, $context = 'call')
    {
        if ($token->getValue() == 'array') {
            $context = 'array';
        }

        $retval = array();
        $token  = $this->tokens->nextToken();
        while (!$token->test(Token::ARGUMENT_LIST_END)) {
            $this->compileToken($token, $retval);
            $token = $this->tokens->nextToken();
        }
        $return = implode('', $retval);
        switch ($context) {
            case 'call':
            default:
                $pattern = '(%s)';
                break;
            case 'array':
                $pattern = $this->options->short_array ? '[%s]' : 'array(%s)';
                break;
            case 'none':
                return $return;
        }
        return sprintf($pattern, $return);
    }

    public function variable($name)
    {
        return '$this->' . $name;
    }

    public function functionCall($name, $arguments = '')
    {
        if ($arguments instanceof Token) {
            $arguments = $this->processArgumentList($arguments);
        } else {
            $arguments = '(' . $arguments . ')';
        }
        return '$this->' . $name . $arguments;
    }

    public function addStringDelimiters(Token $token)
    {
        $value = $token->getValue();
        if ($token->test(Token::STRING)) {
            $value = sprintf("'%s'", str_replace("'", "\'", strtr($value, array('%' => '%%'))));
        }
        return $value;
    }

    public function compileExclusiveRangeOperator(array &$retval)
    {
        $min       = array_pop($retval);
        $max_token = $this->tokens->nextToken();
        if ($max_token->test(Token::LITERAL)) {
            $max = $max_token->getValue();
            if (is_numeric($max)) {
                $max -= 1;
                $retval[] = sprintf('range(%s, %s)', $min, $max);
                return false;
            }
        } elseif ($max_token->test(Token::IDENTIFIER)) {
            $token = $this->tokens->nextTokenIf(Token::ARGUMENT_LIST_START, 'args');
            if ($token) {
                $function_name = $max_token->getValue();
                $max           = $this->functionCall($function_name, $token);
            } else {
                $max = $this->variable($max_token->getValue());
            }
        }
        $retval[] = sprintf('range(%s, %s - 1)', $min, $max);
        return false;
    }

    public function compileRangeOperator(array &$retval)
    {
        $min       = array_pop($retval);
        $max_token = $this->tokens->nextToken();
        if ($max_token->test(Token::LITERAL)) {
            $max = $max_token->getValue();
        } elseif ($max_token->test(Token::STRING)) {
            $max = $this->addStringDelimiters($max_token);
        } elseif ($max_token->test(Token::IDENTIFIER)) {
            $token = $this->tokens->nextTokenIf(Token::ARGUMENT_LIST_START);
            if ($token) {
                $function_name = $max_token->getValue();
                $max           = $this->functionCall($function_name, $token);
            } else {
                $max = $this->variable($max_token->getValue());
            }
        } else {
            $max = $this->compileExpression($this->tokens->nextToken());
        }
        $retval[] = sprintf('range(%s, %s)', $min, $max);
        return false;
    }

    public function compilePowerOperator(array &$retval)
    {
        $base      = array_pop($retval);
        $arg_token = $this->tokens->nextToken();
        if ($arg_token->test(Token::LITERAL)) {
            $exponent = $arg_token->getValue();
        } elseif ($arg_token->test(Token::IDENTIFIER)) {
            $token = $this->tokens->nextTokenIf(Token::ARGUMENT_LIST_START);
            if ($token) {
                $function_name = $arg_token->getValue();
                $exponent      = $this->functionCall($function_name, $token);
            } else {
                $exponent = $this->variable($arg_token->getValue());
            }
        }
        $retval[] = sprintf('pow(%s, %s)', $base, $exponent);
        return false;
    }

    public function compileArrowOperator(array &$retval)
    {
        $retval[] = '->' . $this->tokens->nextToken()->getValue();
    }

    public function compileTildeOperator(array &$retval)
    {
        if (!$this->tokens->test(Token::EXPRESSION_START)) {
            $retval[] = ' . ';
        } else {
            $retval[] = '~';
        }
    }

    public function compileFilterOperator(array &$retval)
    {
        $filter = $this->tokens->nextToken()->getValue();
        if ($filter == 'raw') {
            $this->last_filter_safe = true;
            return false;
        }

        $last_expr = array_pop($retval);
        $token     = $this->tokens->nextTokenIf(Token::ARGUMENT_LIST_START, 'args');
        if ($token) {
            $last_expr .= ', ';
            $last_expr .= $this->processArgumentList($token, 'none');
        }
        $retval[] = $this->compileFunctionCall($filter, $last_expr);
    }

    public function compilePeriodOperator(array &$retval)
    {
        $key      = $this->tokens->nextToken()->getValue();
        $previous = array_pop($retval);

        if (is_numeric($key)) {
            //objects can't have numeric properties
            $retval[] = sprintf('%s[%s]', $previous, $key);
        } else {
            $retval[] = sprintf('$this->getByKey(%s, \'%s\')', $previous, $key);
        }
    }

    public function compileOpeningBracketOperator(array &$retval)
    {
        $retval[] = array_pop($retval) . '[';

        $this->compileToken($this->tokens->nextToken(), $retval);
    }

    public function compileClosingBracketOperator(array &$retval)
    {
        $retval[] = ']';
    }

    public function compileIsOperator(array &$retval)
    {
        if ($this->tokens->nextTokenIf(Token::OPERATOR, '!')) {
            $ret = '!';
        } else {
            $ret = '';
        }
        $test = $this->tokens->nextToken()->getValue();

        $arguments = array_pop($retval);
        $token     = $this->tokens->nextTokenIf(Token::ARGUMENT_LIST_START);
        if ($token) {
            $arguments .= ', ';
            $arguments .= $this->processArgumentList($token, 'none');
        }

        $retval[] = $ret . $this->functionCall($test, $arguments);
    }

    private function compileStringTest(&$retval, $function)
    {
        $last    = array_pop($retval);
        $this->compileToken($this->tokens->nextToken(), $retval);
        $pattern = array_pop($retval);
        return sprintf('$this->%s(%s, %s)', $function, $last, $pattern);
    }

    public function compileStartsWithOperator(array &$retval)
    {
        $retval[] = $this->compileStringTest($retval, 'startsWith');
    }

    public function compileEndsWithOperator(array &$retval)
    {
        $retval[] = $this->compileStringTest($retval, 'endsWith');
    }

    public function compileMatchesOperator(array &$retval)
    {
        $retval[] = $this->compileStringTest($retval, 'isLike');
    }

    public function compileNotStartsWithOperator(array &$retval)
    {
        $this->compileStartsWithOperator($retval);
        $retval[] = '!' . array_pop($retval);
    }

    public function compileNotEndsWithOperator(array &$retval)
    {
        $this->compileEndsWithOperator($retval);
        $retval[] = '!' . array_pop($retval);
    }

    public function compileNotMatchesOperator(array &$retval)
    {
        $this->compileMatchesOperator($retval);
        $retval[] = '!' . array_pop($retval);
    }

    private function compileOperator(Token $token, &$retval)
    {
        $operator = $token->getValue();

        if (!isset(self::$operators [$operator])) {
            $left_op  = array_pop($retval);
            $retval[] = $left_op . $operator;
            return;
        }
        $compiler = self::$operators[$operator];
        if (is_callable(array($this, $compiler))) {
            if ($this->$compiler($retval) === false) {
                return false;
            }
        } else {
            $left_op  = array_pop($retval);
            $retval[] = $left_op . $compiler;
        }
        return true;
    }

    public function compileFunctionCall($function_name, $args = '')
    {
        if ($args instanceof Token) {
            $args = $this->processArgumentList($args, 'none');
        }
        $function = $this->environment->getFunction($function_name);

        $this->last_filter_safe = $function->isSafe();

        if ($function instanceof MethodFunction) {
            $extension_name = $function->getExtensionName();
            $function_name  = $function->getMethod();
            $pattern        = '$this->getExtension(\'%s\')->%s(%s)';

            return sprintf($pattern, $extension_name, $function_name, $args);
        } elseif ($function instanceof SimpleFunction) {
            $function_name = $function->getFunction();
            $pattern       = '%s(%s)';
            return sprintf($pattern, $function_name, $args);
        } else {
            return $this->functionCall($function_name, $args);
        }
    }

    private function compileKeyword($keyword, array &$retval)
    {
        if (!isset(self::$keywords[$keyword])) {
            throw new OutOfBoundsException('Invalid keyword: ' . $keyword);
        }
        $compiler = self::$keywords[$keyword];
        $this->$compiler($retval);
    }

    public function compileInKeyword(array &$retval)
    {
        $token = $this->tokens->nextToken();
        if ($token->test(Token::EXPRESSION_START)) {
            $where = $this->compileExpression($token);
        } elseif ($token->test(Token::IDENTIFIER)) {
            $where = $this->variable($token->getValue());
            $token = $this->tokens->nextTokenIf(Token::ARGUMENT_LIST_START);
        } elseif ($token->test(Token::STRING)) {
            $where = $this->addStringDelimiters($token);
        } else {
            $where = '';
        }
        if ($token && $token->test(Token::ARGUMENT_LIST_START)) {
            $where .= $this->processArgumentList($token);
        }
        $what     = array_pop($retval);
        $retval[] = sprintf('$this->isIn(%s, %s)', $what, $where);
    }

    public function compileNotInKeyword(array &$retval)
    {
        $this->compileInKeyword($retval);
        $retval[] = '!' . array_pop($retval);
    }

    public function compileSetKeyword(array & $retval)
    {
        $token    = $this->tokens->nextToken();
        $retval[] = sprintf('isset(%s)', $this->variable($token->getValue()));
    }

    private function compileToken(Token $token, array &$retval)
    {
        switch ($token->getType()) {
            case Token::KEYWORD:
                $this->compileKeyword($token->getValue(), $retval);
                break;

            case Token:: EXPRESSION_START:
                $compiled_expr = $this->compileExpression($this->tokens->nextToken());
                $retval[]      = '(' . $compiled_expr . ')';
                break;

            case Token::IDENTIFIER:
                $name = $token->getValue();
                //try to compile as a function call
                $next = $this->tokens->nextTokenIf(Token::ARGUMENT_LIST_START, 'args');
                if ($next) {
                    if ($this->environment->hasFunction($name)) {
                        $retval[] = $this->compileFunctionCall($name, $next);
                    } else {
                        $retval[] = $this->functionCall($name, $next);
                    }
                } else {
                    $retval[] = $this->variable($name);
                    return true;
                }
                break;

            case Token::OPERATOR:
                return $this->compileOperator($token, $retval);

            case Token::LITERAL:
            case Token::STRING:
                $retval[] = $this->addStringDelimiters($token);
                break;

            case Token::ARGUMENT_LIST_START:
                $retval[] = $this->processArgumentList($token);
                break;
        }
        return true;
    }

    public function compileExpression(Token $token, $apply_autoescape = false)
    {
        $retval = array();
        while (!$token->test(Token::EXPRESSION_END)) {
            $this->last_filter_safe = false;
            $apply_autoescape &= $this->compileToken($token, $retval);

            $token = $this->tokens->nextToken();
        }

        $string = implode('', $retval);
        if ($apply_autoescape && !$this->last_filter_safe) {
            $string = $this->functionCall('filter', $string);
        }
        return $string;
    }

    private function compileTag(Token $token)
    {
        $tag = $token->getValue();
        $this->tags[$tag]->compile($this);
    }

    private function compileBlock(Token $token)
    {
        $this->tokens->nextToken();
        $tag = $token->getValue();
        $this->tags[$tag]->compile($this);
    }

    private function compileBlockEnd(Token $token)
    {
        $tag = $token->getValue();
        if ($this->tags[$tag] instanceof Block) {
            $this->tags[$tag]->compileEndingTag($this);
        }
        $this->popState();
    }

    public function addOutputStack($output = '')
    {
        $this->output_stack[] = $this->output;
        $this->output         = $output;
    }

    public function popOutputStack()
    {
        $output       = $this->output;
        $this->output = array_pop($this->output_stack);
        return $output;
    }

    private function processState(Token $token)
    {
        switch ($token->getType()) {
            case Token::TEXT:
                $string = strtr($token->getValue(), array("'" => "\'", '\\' => '\\\\'));
                $this->output("echo '%s';", $string);
                break;

            case Token::EXPRESSION_START:
                $retval = $this->compileExpression($this->tokens->nextToken(), true);
                $this->output("echo %s;", $retval);
                break;

            case Token::TAG:
                $this->compileTag($token);
                break;

            case Token::BLOCK_START:
                $this->compileBlock($token);
                break;

            case Token::BLOCK_END:
                $this->compileBlockEnd($token);
                break;
        }
    }

    public function getTemplates()
    {
        return $this->templates;
    }

    public function startTemplate($template)
    {
        $this->template_stack[] = $template . 'Template';
        $this->addOutputStack();
    }

    public function getCurrentTemplate()
    {
        return end($this->template_stack);
    }

    public function endTemplate()
    {
        $template = array_pop($this->template_stack);

        $this->templates[$template] = $this->popOutputStack();
        return $template;
    }

    public function output($string)
    {
        $args   = func_get_args();
        $string = array_shift($args);
        $row    = str_repeat(' ', $this->indentation * 4);
        $row .= vsprintf($string, $args);
        $this->output .= rtrim($row) . "\n";
    }

    public function raw($string)
    {
        $this->output .= $string;
    }

    public function indent()
    {
        $this->indentation++;
    }

    public function outdent()
    {
        if ($this->indentation == 0) {
            throw new BadMethodCallException('Cannot outdent more.');
        }
        $this->indentation--;
    }

    public function pushState($state, $data = null)
    {
        $this->states[]     = $state;
        $this->state_data[] = $data;
    }

    public function popState()
    {
        array_pop($this->state_data);
        return array_pop($this->states);
    }

    public function getState()
    {
        return end($this->states);
    }

    public function getStateData()
    {
        return end($this->state_data);
    }

    public function isState($state)
    {
        return $this->getState() === $state;
    }

    public function compilePartial($template)
    {
        $this->output            = '';
        $this->tokens            = $this->parser->parse($template);
        $this->states            = array(self::STATE_TEXT);
        $this->template_stack    = array();
        $this->output_stack      = array();
        $this->templates         = array();
        $this->extended_template = 'Template';
        $this->indentation       = 2;

        $token = $this->tokens->nextToken();
        while (!$token->test(Token::EOF)) {
            $this->processState($token);
            $token = $this->tokens->nextToken();
        }
        $output = $this->output;

        $this->output      = '';
        $this->indentation = 0;
        return $output;
    }

    private function compileTemplate($name, $template)
    {
        $this->output('public function %s()', $name);
        $this->output('{');
        $this->outdent();
        $this->output($template);
        $this->indent();
        $this->output('}');
        $this->output('');
    }

    public function setExtendedTemplate($template)
    {
        $this->extended_template = $template;
    }

    public function extendsTemplate()
    {
        return $this->extended_template !== self::$main_template;
    }

    public function getExtendedTemplate()
    {
        if (!$this->extendsTemplate()) {
            return false;
        }
        return $this->extended_template;
    }

    public function sanitizeTemplateClass($template)
    {
        return strtr($template, '/', '\\');
    }

    public function getClassForTemplate($template)
    {
        if (!$template) {
            return 'Modules\Templating\Template';
        }
        $namespace = $this->options->cache_namespace;
        $namespace .= '\\' . strtr($template, '/', '\\');
        return $namespace . 'Template';
    }

    public function getTokenStream()
    {
        return $this->tokens;
    }

    public function compile($template, $class)
    {
        $main              = $this->compilePartial($template);
        $extended_template = $this->getExtendedTemplate();

        $pos       = strrpos($class, '\\');
        $namespace = substr($class, 0, $pos);
        $classname = substr($class, $pos + 1);

        $use_namespace = $this->getClassForTemplate($extended_template);
        $this->output('<?php');
        $this->output('');
        $this->output('namespace %s;', $namespace);
        $this->output('');
        $this->output('use %s as BaseTemplate;', $use_namespace);
        $this->output('');
        $this->output('class %s extends BaseTemplate', $classname);
        $this->output('{');
        $this->indent();
        if ($this->extendsTemplate()) {
            $this->output('public function getParentTemplate()');
            $this->output('{');
            $this->indent();
            $this->output('return \'%s\';', $extended_template);
            $this->outdent();
            $this->output('}');
            $this->output('');
        }
        if (!$this->extendsTemplate()) {
            $this->compileTemplate('render', $main);
        } foreach ($this->templates as $name => $template) {
            $this->compileTemplate($name, $template);
        }
        $this->outdent();
        $this->output('}');
        $this->output('');
        $this->output('?>');
        return $this->output;
    }
}
