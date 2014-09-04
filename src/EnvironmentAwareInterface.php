<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty;

interface EnvironmentAwareInterface
{
    /**
     * @param Environment $environment
     */
    public function setEnvironment(Environment $environment);
}
