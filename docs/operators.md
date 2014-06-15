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


Logic operators
--------

 * `and` or `&&`: `a && b` results in `true` if and only if both `a` and `b` are true.
 * `or` or `||`: `a || b` results in `true` if either `a` and `b` are true.
 * `!`: negates the logic value of the expression that follows
 * `xor`: `a xor b` results in `true` if `a` or `b` is true but not both.
