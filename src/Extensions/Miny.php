<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Extensions;

use Miny\Application\Application;
use Modules\Templating\Compiler\Functions\MethodFunction;
use Modules\Templating\Compiler\Functions\SimpleFunction;
use Modules\Templating\Extension;

class Miny extends Extension
{
    /**
     * @var Application
     */
    private $application;

    public function __construct(Application $app)
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
        $functions = array(
            new MethodFunction('route', 'routeFunction'),
            new MethodFunction('request', 'requestFunction'),
        );
        if($this->application->isDeveloperEnvironment()) {
            $functions[] = new SimpleFunction('dump', 'var_dump');
        }
        return $functions;
    }

    public function routeFunction($route, array $parameters = array())
    {
        return $this->application->getContainer()->get('\\Miny\\Routing\\Router')->generate($route, $parameters);
    }

    public function requestFunction($url, $method = 'GET', array $post = array())
    {
        $factory = $this->application->getContainer();

        $main = $factory->get('\\Miny\\HTTP\\Response');
        $main->addContent(ob_get_clean());

        $request  = $factory->get('\\Miny\\HTTP\\Request')->getSubRequest($method, $url, $post);
        $response = $factory->get('\\Miny\\Application\\Dispatcher')->dispatch($request);

        $main->addResponse($response);
        ob_start();
    }
}
