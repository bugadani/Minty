Environment options
========
Minty provides a handful of options to customize the syntax and some of its features.

Syntactic options
--------
These options affect the templates Minty is able to work with.

 * `block_end_prefix`
   This option tells Minty what the block ending tags look like. For example when this option has
   the value 'end', `if` blocks will only be ended by `endif` tags.
   **Default value:** `/`
 * `delimiters`
   This is a two-dimensional array that holds the opening and closing delimiters for tags and
   comment blocks.
   **Default value:**
    - tag: `{`, `}`
    - comment: `{#`, `#}`
    - whitespace control tag: `{-`, `-}`
 * `fallback tag`
   This option specifies which tag should be used when the tag name is not explicitly written down.
   The default value is `print`, so basically every expression will be printed.

Other options
--------

 * `autofilter`
   Sets the automatic filtering mode. Values:
     - `0`, `false`: don't filter output automatically.
     - `1`: filter output based on file extension
 * `cache`
   The path of the cache directory for the compiled templates to be saved in. To disable caching,
   set to `false`.
 * `debug`
   When set to `true`, extra information is compiled in form of comments into the template.
 * `default_autofilter_strategy`
   This options affects how values are filtered. When filtering is set to automatic mode this option
   works as if files without extension had the extension set by this option. For more information,
   see [filtering](filtering.md).
 * `strict_mode`
   When set to `true`, accessing variables that are not present in the current context will result
   in an error. When disabled, accessing an unset variable will return its name.
   *Note*: functionality that checks if a variable is set is not affected by this option.
 * `tag_consumes_newline`
   Controls whether the first newline after tags should be stripped automatically.
