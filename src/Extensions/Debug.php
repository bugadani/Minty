<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Minty\Extensions;

use Minty\Compiler\TemplateFunction;
use Minty\Extension;

class Debug extends Extension
{

    public function getExtensionName()
    {
        return 'debug';
    }

    public function getFunctions()
    {
        return array(
            new TemplateFunction('dump', 'var_dump')
        );
    }

}
