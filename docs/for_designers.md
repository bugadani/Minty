Minty for template designers
========
This document describes the basics of the templating language provided by the Minty library.

Outline
--------
Templates are simple text files that have the `.tpl` extension. Template files are used to generate textual output
such as HTML, XML, LaTeX, etc.

A template consists of **expressions** which get replaced with their values and **tags** that are responsible for
 basic functions and control the logic of the template.

These building blocks (tags) are delimited by `{ ... }`.

Tags
--------
A tag is an expression in braces. Tags usually start with the name of the tag that is followed by a
tag-specific sequence of keywords and parameters. These tags get replaced by something that is determined by
the tag itself, for example an `include` tag will be replaced by whatever
the included template displays.

Blocks
--------
Blocks are special tags: they have an ending tag that closes the block. Blocks operate on the template that is
enclosed by the opening and closing tag. For example, an `if` block displays the enclosed template only when
the condition is true.

    { if $number = 2 }
        The number was two.
    { else }
        The number was { $number }
    { /if }

Comments
--------
A template may contain comments, notes that will only be present in the source file and not in the output.
Comments are delimited by `{# ... #}`.

Variables
--------
Variables are prefixed with a dollar (`$`) sign that is followed by the name of the variable.

### Setting variables ###

Setting a variable can be done using the assignment syntax. This syntax is a syntactic sugar for the `set` tag.
This contains the variable followed by a colon (`:`) followed by the value expression.

Example: `{$foo: 'bar'}`

Literal data
--------
Literals are the simplest expressions. They represent data types like strings, numbers and arrays.
The simplest literal is `null` which represent no value.

### Strings ###

Strings are delimited by double (`"`) or single (`'`) quotes.
Alternatively for single words can be prefixed by a colon (`:`) and they will be terminated at the next whitespace.
Example strings: `"this is a string"`, `'this is a string'`, `:also_a_string`

Strings delimited by quotes may contain the delimiter if it is preceded by a backslash (`\\`).

### Numbers ###

Integers and floating point numbers (a number with a fraction) can be used by just writing them down.
A number is considered a floating point number when it contains a dot (`.`) otherwise it is an integer.

### Boolean (truth) values ###

`true` and `false` represent simple truth values. They are mostly used in conditions.

### Arrays ###

An array element has an index (a key) and a value. An array is a set (or list) of these elements. An array begins with
an opening bracket (`[`) and ends with a closing bracket (`]`). Array elements are separated with commas (`,`).
Keys and values may be separated by either a colon (`:`) or a double arrow (`=>`) sign.

Example: `['foo': 'bar', :baz => 'foobar']`

Here, `foo` and `baz` are keys, `bar` and `foobar` are values.
The keys are optional. When not specified, keys begin with 0 and each element has a key that is one
greater than the previous.

Example: `[:foo, :bar, :baz]` - here `foo` has index 0, `baz` has index 2.

Any valid expression that evaluates to a string or integer may be used as array key. Also, bare words
are accepted and treated like strings to provide a cleaner syntax. Note that this only applies to array keys.

    [key: 'value', other: 'other value']

Arrays can be nested - `[ :foo => [1, 2], :bar => [3, 4] ]`

#### Accessing array elements ####

Once can access an array element using either the dot (`.`) sign or the 'subscript' syntax (`[]`).

Example: `$foo['bar'].baz`

Functions
--------
A function is a word followed by a a list of arguments in parentheses. These functions return a result
based on what arguments are passed to them. There are a number of different functions available that
can for example be used to make a string upper case, return the first element of an array, etc.

Examples: `upper('string')`, `min(3, 5)`

### Alternative function syntax ###
There is an alternative way to call functions which may suit some use cases better than the classical way.
In this case the first argument is followed by a pipe sign (`|`) which is followed by the name of the function
and the remaining arguments in parentheses, if any.

Examples: `:first_argument|upper`, `'link_title'|link_to($url, $attributes)`

Whitespace control
--------
By default, Minty does not modify whitespaces in templates. This means that every space, tab and newline character
is preserved outside tags.

In some situations however, you may want to manually tell Minty to remove whitespaces around tags to make the HTML
output nicer. This can be done using the whitespace control tag delimiters (`{-` and `-}` by default),
which trim whitespaces before and/or after the tag.

Also, an environment option is available to control whether Minty should automatically remove the first
newline character after each tag.

Example:

    <li>    {- 'no spaces' -}    </li>
    {# this will output '<li>no spaces</li>' #}

    <li>    {'no spaces after' -}    </li>
    {# this will output '<li>    no spaces after</li>' #}
