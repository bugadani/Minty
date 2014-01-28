<?php

namespace Modules\Templating\Compiler;

class StreamTest extends \PHPUnit_Framework_TestCase
{

    public function testStreamFunctions()
    {
        // This can be done using integers because this part of Stream does not depend on the type of the data.
        $stream = new Stream(array(1, 2, 3));
        $this->assertEquals(null, $stream->current());
        $this->assertEquals(1, $stream->next());
        $this->assertEquals(2, $stream->next());
        $this->assertEquals(3, $stream->next());
        $this->assertEquals(3, $stream->current());
        $this->assertEquals(2, $stream->prev());
        $this->assertEquals(1, $stream->prev());
    }
}
