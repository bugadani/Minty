<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Extensions;

use Modules\Templating\Compiler\TemplateFunction;
use Modules\Templating\Extension;

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
