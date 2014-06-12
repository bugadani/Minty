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
use Modules\Templating\Compiler\TemplateFunction;
use Modules\Templating\Context;
use Modules\Templating\Environment;
use Modules\Templating\Extension;
use Traversable;

class StandardFunctions extends Extension
{

    public function getExtensionName()
    {
        return 'standard_functions';
    }

    public function getFunctions()
    {
        $namespace = '\\' . __NAMESPACE__;

        return array(
            new TemplateFunction('abs'),
            new TemplateFunction('attributes', $namespace . '\template_function_attributes', array('is_safe' => true)),
            new TemplateFunction('batch', $namespace . '\template_function_batch'),
            new TemplateFunction('capitalize', 'ucfirst'),
            new TemplateFunction('count', null, array('is_safe' => true)),
            new TemplateFunction('cycle', $namespace . '\template_function_cycle'),
            new TemplateFunction('date_format', $namespace . '\template_function_dateFormat'),
            new TemplateFunction('extract', $namespace . '\template_function_extract', array('needs_context' => true)),
            new TemplateFunction('first', $namespace . '\template_function_first'),
            new TemplateFunction('format', 'sprintf'),
            new TemplateFunction('is_int'),
            new TemplateFunction('is_numeric'),
            new TemplateFunction('is_string'),
            new TemplateFunction('join', $namespace . '\template_function_join'),
            new TemplateFunction('json_encode'),
            new TemplateFunction('keys', 'array_keys'),
            new TemplateFunction('last', $namespace . '\template_function_last'),
            new TemplateFunction('length', $namespace . '\template_function_length', array('is_safe' => true)),
            new TemplateFunction('link_to', $namespace . '\template_function_linkTo', array('is_safe' => true)),
            new TemplateFunction('lower', 'strtolower'),
            new TemplateFunction('ltrim'),
            new TemplateFunction('max'),
            new TemplateFunction('merge', 'array_merge'),
            new TemplateFunction('min'),
            new TemplateFunction('nl2br', null, array('is_safe' => true)),
            new TemplateFunction('number_format', null, array('is_safe' => true)),
            new TemplateFunction('pluck', $namespace . '\template_function_pluck'),
            new TemplateFunction('random', $namespace . '\template_function_random'),
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
    if ($data instanceof Traversable) {
        return iterator_to_array($data);
    }
    if (is_array($data)) {
        return $data;
    }

    throw new InvalidArgumentException('Expected an array or traversable object.');
}

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

function template_function_extract(Context $context, $source, $keys)
{
    foreach ((array) $keys as $key) {
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

function template_function_linkTo($label, $url, array $attrs = array())
{
    $attrs['href'] = $url;

    $attributes = template_function_attributes($attrs);

    return "<a{$attributes}>{$label}</a>";
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
    }
    if ($limit === null) {
        return explode($delimiter, $string);
    }

    return explode($delimiter, $string, $limit);
}

function template_function_truncate($string, $length, $ellipsis = '...')
{
    if (strlen($string) > $length) {
        $string = substr($string, 0, $length);
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
