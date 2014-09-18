The `do` tag
========

The `do` tag lets you evaluate expressions without outputting anything.

Example:

    { $a: 1 }
    { do $a++ }
    { $a } {# outputs 2 #}
