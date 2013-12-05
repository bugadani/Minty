<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Compiler\Extensions;

use Miny\Application\BaseApplication;
use Modules\Templating\Compiler\Functions\MethodFunction;
use Modules\Templating\Extension;

class Miny extends Extension
{
    private $application;

    public function __construct(BaseApplication $app)
    {
        parent::__construct();
        $this->application = $app;
    }

    public function getExtensionName()
    {
        return 'miny';
    }

    public function getFunctions()
    {
        return array(
            new MethodFunction('route', 'filter_route'),
        );
    }

    public function filter_route($route, array $parameters = array())
    {
        return $this->application->router->generate($route, $parameters);
    }
}
