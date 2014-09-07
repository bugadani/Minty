The `set` and `unset` tags
========

The `set` and `unset` tags are used to set or unset variables, respectively.

Syntax:

    { set $<variableName>: <value>[, $...]}
    { unset $<variableName>[, $...]}

Example:

    { set $foo: 'bar' } {# $foo now contains string 'bar' #}
    { set $foo: 'bar', $baz: 'foobar' } {# $foo now contains string 'bar' and $baz contains 'foobar' #}
    { unset $foo }
    { $isFooSet: $foo is set }{# $isFooSet is boolean false #}

