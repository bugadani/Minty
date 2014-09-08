Available operators
========

Arithmetic (math) operators
--------
Note: while these operators are not particularly useful for templates, they are present for completeness' sake.

You can use the standard arithmetic operators (`+`, `-`, `*`, `/` and `%`) that operate on numbers.

Additionally, the exponential operator (`**` or `^˙) is available that computes the n-th power of a number.
This operator is right associative meaning that it is evaluated from right to left.

Example: `2 ** 3 ** 2` results in 512, while `(2 ** 3) ** 2` results in 64.

Sign operators (`+` and `-`) are also available.

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

Test operators
--------
Test operators are particularly useful in `if` blocks.
They can be used to test for properties in a simple and easily readable way.

The available test operators are:

 * `in` tests if a value can be found in a string or an array
 * `divisible by` tests if the number on the left side is divisible by the number on the right side
 * `ends with` and `starts with` tests if the string on the left side starts or ends with the string on the right side
 * `matches` or `is like` tests if the string on the left side matches the regular expression on the right side

    Note: the regular expression matching is performed using PHP's [preg_match](http://php.net/preg_match) function.

 * `is empty` tests if a variable is considered [empty](http://php.net/empty)
 * `is set` checks if the variable on the left hand side exists

The negated tests are also available.

 * `not in`
 * `not divisible by` or `is not divisible by`
 * `not ends with` or `does not end with`
 * `does not match`, `is not like` or `not matches`
 * `not starts with` or `does not start with`
 * `is not empty`
 * `is not set`

Comparison operators
--------

The following comparison operators are available:

 * `<` or `is less than`
 * `<=` or `is less than or equals`
 * `>` or `is greater than`
 * `>=` or `is greater than or equals`
 * `=` or `equals`
 * `==`, `is same as` or `is identical`

     Note: two variables are identical when their type **and** value matches or they reference the same object instance

 * `!=`, `<>` or `does not equal`
 * `!==`, `is not same as` or `is not identical`

Logic operators
--------

 * `and` or `&&`: `a && b` results in `true` if and only if both `a` and `b` are true.
 * `or` or `||`: `a || b` results in `true` if either `a` and `b` are true.
 * `!`: negates the logic value of the expression that follows
 * `xor`: `a xor b` results in `true` if `a` or `b` is true but not both.

Other operators
--------

### The ternary operators
Minty support the C-like ternary operator (`?:`). Just like in PHP, both the full and the short forms are supported.

Examples:
    { $foo|length = 3 ? 'Length is 3' : 'Length is not 3' }

    { $bool: false }
    { $bool ?: 'some string' }{# prints 'some string' #}

Minty supports a variation of the ternary operator similar to the null coalescing operator found in C#.
The operator `??` evaluates the left hand side expression using `isset()` and returns either the left side if it is set (i.e. the variable exists and it is not null), or the right hand side.
