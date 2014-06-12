The `list` tag
========

The `list` tag is used to display a list of data using a particular template. The template will be
displayed for each item in the list.

    { list $posts using 'posts/list_item' }

Like `include` and `embed`, `list` accepts any expression as template name. If an array is passed
as template the first template that exists will be displayed.

See also:

 * [include](tags/include.md)
 * [embed](tags/embed.md)
 * [for](tags/for.md)
