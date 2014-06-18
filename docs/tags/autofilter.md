The `autofilter` tag
========

The `autofilter` tag is used to change the filtering strategy used in a block of code.
The filtering strategy is chosen by the extension of the current template file. If the file has no extension,
the default escaping strategy is used which is HTML.

Allowed options
--------

The following options are accepted both as strings and bare words without the string delimiters.

 * `off`, `disabled`: disables automatic output filtering. This has the same effect as applying the raw
    filter to every output.
 * `on`, `enabled`, `auto`: enables automatic output filtering

Specific filtering strategies are accepted only as strings:

 * `html`: sets the environment to HTML. Output will be escaped using `htmlspecialchars`
 * `json`: sets the environment to JSON. Output will be escaped using `json_encode`

This list may be expanded by defining custom filtering functions.

Nesting `autofilter` blocks
--------

When needed, `autofilter` tags may be nested. In this case the outer filtering strategy will not be effective
inside the inner block.
