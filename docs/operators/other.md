Other operators
========

Bitwise operators
--------
Note: while these operators are not particularly useful for templates, they are present for completeness' sake.

The following bitwise operators are available:

 * `b-and`: bitwise and operator
 * `b-or`: bitwise or operator
 * `~`: bitwise not operator
 * `b-xor`: bitwise exclusive or operator
 * `<<`: shift left operator
 * `>>`: shift right operator

The ternary operator (`?:`)
--------
Minty support the C-like ternary operator (`?:`). Just like in PHP, both the full and the short
forms are supported.
Note: the `?:` operator first checks if the variable (or array index or property) exists before
checking its value. When a particular value does not exist, it is treated as if it were `false`.

Examples:
    { $foo|length = 3 ? 'Length is 3' : 'Length is not 3' }

    { $bool: false }
    { $bool ?: 'some string' }{# prints 'some string' #}

Null coalescing operator (`??`)
--------

Minty supports a variation of the ternary operator similar to the null coalescing operator found
in C#.

This operator returns the right hand side if and only if the expression on the left hand side
is a variable (or array index or object property) is not set or is `null`.

Example:

    { $variable: $somethingThatDoesNot[:Exist] ?? 3 } {# $variable will be 3 #}
    { $variable ?? 2 } {# outputs 3 because $variable is set to 3 in the previous line #}
