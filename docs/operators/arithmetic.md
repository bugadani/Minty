Arithmetic (maths) operators
========

`+`, `-`, `*`, `/`
--------

`+`, `-`, `*`, and `/` work pretty much as one would expect.

 * `$a + $b` result in the sum of the variables
 * `$a - $b` result in the difference of the variables
 * `$a * $b` multiplies `$a` by `$b`
 * `$a / $b` divides `$a` by `$b`
 * `-$a` results in the opposite of `$a`

The exponential operators (^, **)
--------

`$a ^ $b` raises `$a` to the power of `$b`, i.e. `2^3` results in 8. The operator is right
associative, meaning that `$a ^ $b ^ $c` is equal to `$a ^ ($b ^ $c)`.

The remainder (%) and modulo (mod) operators
--------

The `%` operator divides two numbers and returns the remainder. The remainder has the same sign as the
dividend.

Examples:

    { 5 % 3 }   {# 2 #}
    { 5 % -3 }  {# 2 #}
    { -5 % 3 }  {# -2 #}
    { -5 % -3 } {# -2 #}

The `mod` operator also returns the remainder of a division. The difference is, that using this
operator the remainder has the same sign as the divisor.

Examples:

    { 5 mod 3 }   {# 2 #}
    { 5 mod -3 }  {# -1 #}
    { -5 mod 3 }  {# 1 #}
    { -5 mod -3 } {# -2 #}
