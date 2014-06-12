The `include` tag
========

The `include` tag is used to display a particular template.

    { include 'header' }
    Content goes here
    { include 'footer' }

By default the included template has access to every variable present in the current context.
You can specify the variables to be passed with the `using` keyword.

    {# only the 'post' variable will be accessible #}
    { include 'posts/show_item' using [:post => $post] }

`include` accepts any expression as template name.

    { $template: 'some_template' }
    { include $template }

A list of templates can be passed that will be checked for existence. The first template that exists will be displayed.

    { include ['template_1', 'template_2'] }

See also:

 * [list](tags/list.md)
 * [embed](tags/embed.md)
 * [for](tags/for.md)
