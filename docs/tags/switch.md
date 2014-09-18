The `switch`-`case`-`else` structure
========

`switch` is an other classic control flow statement that Minty supports. `switch` works by comparing
a value to a list of values.

Syntax:

    { switch <some value> }
    { case <pattern 1> }
        branch for when <some value> equals <pattern 1>
    { case <pattern 2> }
        branch for when <some value> equals <pattern 2>
    { else }
        branch for when <some value> does not equal to any of the patterns
    { /switch }

Note: both `case` and `else` branches are optional, but at least one of them is required.
Also, only one `else` branch is accepted.

See also:

 * [if](tags/if.md)
