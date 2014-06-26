The `define` and `display` tags
===============================

The `define` tag is used to create a small named template-part that can be reused later.
Content inside a `define` tag will be stored and it can be displayed using the `display` tag later.

Using define is similar to the `block` tag, the difference is that
by default the block will not be present in the output.

    {define button}
    <button>{$title}</button>
    {/define}

The defined block can be displayed later on using the `display` tag.

The used variables can come from the current context

    {for $title in ['Title one', 'Title two']}
        {display button}
    {/for}

Or the used variables can be fed with the `using` keyword and an array

    {display button using [title: 'Button title']}
