<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating;

use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Miny\Application\Application;
use Miny\Extendable;
use Traversable;

class Plugins extends Extendable
{
    private static $remapped = array(
        'merge'      => 'array_merge',
        'title_case' => 'ucwords',
        'upper_case' => 'strtoupper',
        'lower_case' => 'strtolower',
        'keys'       => 'array_keys',
        'capitalize' => 'ucfirst',
        'format'     => 'sprintf',
        'sort'       => 'asort',
        'striptags'  => 'strip_tags'
    );
    private $application;
    private $router;

    public function __construct(Application $application)
    {
        $this->application = $application;
        $this->router      = $application->router;
    }

    public function batch($data, $size, $no_item = null)
    {
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }
        $result = array_chunk($data, abs($size), true);
        if ($no_item == null) {
            return $result;
        }
        $last = count($result) - 1;
        $result[$last] = array_pad($result[$last], $size, $no_item);
        return $result;
    }

    public function cycle(array &$array)
    {
        $element = each($array);
        if ($element === false) {
            reset($array);
            $element = each($array);
        }
        return $element['value'];
    }

    public function first($array)
    {
        if (is_string($array)) {
            return substr($array, 0, 1);
        }
        $first = reset($array);
        return is_string($first) ? $first : current($first);
    }

    public function last($array)
    {
        if (is_string($array)) {
            return substr($array, -1);
        }
        $last = end($array);
        return is_string($last) ? $last : current($last);
    }

    public function length($data)
    {
        if (is_string($data)) {
            return strlen($data);
        }
        if (is_array($data) || $data instanceof Countable) {
            return count($data);
        }
        throw new InvalidArgumentException('Reverse expects an array, a string or a Countable instance');
    }

    public function nl2br($string)
    {
        $string = $this->filter($string);
        return nl2br($string);
    }

    public function reverse($data, $preserve_keys = false)
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

    public function random($data = null)
    {
        if($data === null) {
            return rand();
        }
        if(is_numeric($data)) {
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

    public function slice($data, $start, $length, $preserve_keys = false)
    {
        if (is_string($data)) {
            return substr($data, $start, $length);
        }
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }
        if (is_array($data)) {
            return array_slice($data, $start, $length, $preserve_keys);
        }
        throw new InvalidArgumentException('Slice expects an array or a string');
    }

    public function join($data, $glue = '')
    {
        if ($data instanceof Traversable) {
            $data = iterator_to_array($data);
        }
        return implode($glue, $data);
    }

    public function arguments(array $args)
    {
        $arglist = array();
        foreach ($args as $name => $value) {
            $arglist[] = sprintf(' %s="%s"', $name, $value);
        }
        return implode('', $arglist);
    }

    public function split($string, $delimiter = '', $limit = null)
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

    public function replace($string, $search, $replace)
    {
        return str_replace($search, $replace, $string);
    }

    public function url_encode($data, $raw)
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

    public function pluck($array, $key)
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

    public function route($route, array $parameters = array())
    {
        return $this->router->generate($route, $parameters);
    }

    public function link_to($label, $url, array $args = array())
    {
        $args['href'] = $url;
        return sprintf('<a%s>%s</a>', $this->arguments($args), $label);
    }

    public function __call($method, $args)
    {
        if (isset(self::$remapped[$method])) {
            return call_user_func_array(self::$remapped[$method], $args);
        }
        return parent::__call($method, $args);
    }
}
