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
        return $this->application->router->generate($route, $parameters);
    }

    public function requestFunction($url, $method = 'GET', array $post = array())
    {
        $main = $this->application->response;
        $main->addContent(ob_get_clean());

        $request  = $this->application->request->getSubRequest($method, $url, $post);
        $response = $this->application->dispatch($request);

        $main->addResponse($response);
        ob_start();
    }
}
