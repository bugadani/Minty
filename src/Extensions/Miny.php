<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Extensions;

use Miny\Application\Dispatcher;
use Miny\Factory\Container;
use Miny\Router\RouteGenerator;
use Modules\Templating\Compiler\TemplateFunction;
use Modules\Templating\Extension;

class Miny extends Extension
{
    /**
     * @var Container
     */
    private $container;

    /**
     * @var Dispatcher
     */
    private $dispatcher;

    /**
     * @var RouteGenerator
     */
    private $routeGenerator;

    /**
     * @param Container      $container
     * @param Dispatcher     $dispatcher
     * @param RouteGenerator $routeGenerator
     */
    public function __construct(
        Container $container,
        Dispatcher $dispatcher,
        RouteGenerator $routeGenerator
    ) {
        parent::__construct();
        $this->container      = $container;
        $this->dispatcher     = $dispatcher;
        $this->routeGenerator = $routeGenerator;
    }

    public function getExtensionName()
    {
        return 'miny';
    }

    public function getFunctions()
    {
        $functions = array(
            new TemplateFunction('route', array($this->routeGenerator, 'generate')),
            new TemplateFunction('request', array($this, 'requestFunction')),
        );

        return $functions;
    }

    public function requestFunction($url, $method = 'GET', array $post = array())
    {
        $main = $this->container->get('\\Miny\\HTTP\\Response');
        $main->addContent(ob_get_clean());

        $response = $this->dispatcher->dispatch(
            $this->container->get('\\Miny\\HTTP\\Request')
                ->getSubRequest($method, $url, $post)
        );

        $main->addResponse($response);
        ob_start();
    }
}
