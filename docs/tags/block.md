The `block` tag
===============================

The `block` tag is used in template inheritance to define pieces of code that can be replaced
in the parent template, and pieces of code that will be inserted into the parent template.

    {# parent template (parent.tpl) #}
    { block button }
    <button>{$title}</button>
    { /block }

    {# child template #}
    { extends 'parent' }

    {# define block that will replace the 'button' block in parent.tpl #}
    { block button }
    <button>With a constant label</button>
    { /block }

See also:

 * [define and display](tags/define_and_display.md)
