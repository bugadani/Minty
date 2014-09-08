The `if`-`elseif`-`else` structure
========

Minty supports the basic `if`-`then`-`else` type program flow control structures. This structure is
similar to the `if` statement in PHP.

Syntax:

    { if <expression> }
        branch for when <expression> is true
    { elseif <other expression> }
        branch for when <other expression> is true
    { else }
        default branch
    { /if }

Minty supports both `elseif` and `else` tags to indicate alternative and default branches.

Note: both `elseif` and `else` branches are optional.
Also, while any number of `elseif` branches may be present, only one `else` tag is accepted.
