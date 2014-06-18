The `capture` tag
========

The capture tag allows you to capture output into a variable.

Usage
--------

    { capture into $variable }
    Some content that will be captured.
    By printing $variable, the text in this block will be printed.
    { endcapture }
    { $variable }
