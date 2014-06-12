The `extends` tag
========

The `extends` tag is used to signal that the current template is extending an other template.
The blocks specified in the current template will replace the blocks in the extended one.

Child template
--------

A simple child template may look like this:

    {# template.tpl #}
    { extends 'layout' }
    { block content }
        This content will be displayed
    { endblock }

Extended (parent) template
--------

A simple layout file for the above child may look like this:

    {# layout.tpl #}
    <!DOCTYPE html>
    <html>
        <head>
            <title>{ block title }My Webpage{ endblock }</title>
        </head>
        <body>
            <div id="content">{ block content }{ endblock }</div>
            <div id="footer">
                <a href="http://domain.invalid/">you</a> &copy; 2014
            </div>
        </body>
    </html>
