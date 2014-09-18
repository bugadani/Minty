The `import` tag
========

The `import` tag is used to import a list of blocks into the current template, thus enabling some sort
of horizontal code reuse.

`import` loads blocks defined by both `block` and `define`.

Basic syntax:

    { import <templates> from <source> }

Example:

    {# template 'source.tpl' #}
    { define header }
    <h1>{ $header }</h1>
    { /define }

    {# main template #}
    { import all from 'source' }
    {display header using [:header => 'This will be inside <h1> tags']}

A number of values can be put in place of `<templates>`.

  * `all` will import all blocks into the current template
  * A string value to import a single block
  * An array of strings to import a number of blocks
    This can either be a simple list of names, also a key-value pair.
    When using a simple list, blocks are not renamed.
    Using a key-value pair, the block identified by the key will be imported as the block identified
    by the value.

Example of renaming blocks:

    {# template 'source.tpl' #}
    { define header }
    <h1>{ $header }</h1>
    { /define }

    {# main template #}
    { import [:header => :alias] from 'source' }
    {display alias using [:header => 'This will be inside <h1> tags']}

Note: similar to defining blocks with a name that already exists, `import` will throw an error when
trying to import blocks with names that are already present in the current template.
Also, when a block is not found, an error is thrown.

See also:

 * [block](tags/block.md)
 * [define and display](tags/define_and_display.md)
