<?php
/**
 * Created by PhpStorm.
 * User: ASUS T100
 * Date: 2014.06.14.
 * Time: 21:46
 */

namespace Minty\IntegrationTests;

use Minty\Extensions\Optimizer;
use Minty\TemplateLoaders\StringLoader;

class OptimizerIntegrationTest extends CoreIntegrationTest
{
    public function getEnvironment(StringLoader $loader)
    {
        $env = parent::getEnvironment($loader);
        $env->addExtension(new Optimizer());

        return $env;
    }
}
