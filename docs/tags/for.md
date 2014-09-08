The `for` tag
========

The `for` tag is used to iterate over an array or an iterator.

Simple `for` loops
--------

The following example takes an array and displays its elements in a HTML list.

    { $foo = [1, 2, 3] }
    <ul>
    { for $element in $foo }
        <li>{$element}</li>
    { /for }
    </ul>

It is possible to get the current key in a variable using the `:` or `=>` signs.

    { $foo = [:foo, :bar, :baz] }
    <ul>
    { for $key: $element in $foo } {# for $key => $element would also work #}
        <li>Element #{$key} is "{$foo}"</li>
    { /for }
    </ul>

The empty case
--------

It is possible that a for loop receives an empty array. To handle this case, an optional `else` branch may be defined.

    { for $i in [] }
        this will never be shown
    { else }
        this branch will be shown
    {/for}

Listing an array into variables
--------
Minty supports iterating over a list of arrays. Provided that `$foo` is an array of arrays where each
sub-array has four elements, the following prints out the elements of `$foo` in a table.

    <table>
    <tr>
        <th>Row #</th>
        <th>Element 1</th>
        <th>Element 2</th>
        <th>Element 3</th>
        <th>Element 4</th>
    </tr>
    { for $rowNum => $el1, $el2, $el3, $el4 in $foo }
    <tr>
        <td>{$rowNum}</td>
        <td>{$el1}</td>
        <td>{$el2}</td>
        <td>{$el3}</td>
        <td>{$el4}</td>
    </tr>
    {/for}
    </table>
