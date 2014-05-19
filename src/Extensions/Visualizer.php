<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Extensions;

use Miny\Log\AbstractLog;
use Modules\Templating\Extension;
use Modules\Templating\Extensions\Miny\NodeTreeVisualizer;

class Visualizer extends Extension
{
    /**
     * @var AbstractLog
     */
    private $log;

    public function __construct(AbstractLog $log)
    {
        $this->log = $log;
    }

    public function getExtensionName()
    {
        return 'visualizer';
    }

    public function getNodeVisitors()
    {
        $visitors = array(
            new NodeTreeVisualizer($this->log)
        );

        return $visitors;
    }

}
