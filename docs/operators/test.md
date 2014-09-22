Test operators
========

Test operators compare their value to a set of rules and return, whether the value matches that
particular rule. This is especially useful in conditional structures like the `if` tag or the `?:`
operator.

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
