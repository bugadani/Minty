<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\TemplateLoaders;

use Modules\Templating\Environment;

class ErrorTemplateLoader extends StringLoader
{

    public function setEnvironment(Environment $environment)
    {
        parent::setEnvironment($environment);
        $this->addTemplate(
            '__compile_error_template',
            $this->getCompileErrorTemplate(
                $environment->getOption('block_end_prefix', 'end')
            )
        );
    }

    private function getCompileErrorTemplate($closingTagPrefix)
    {
        $source = "{block error}<h1>Failed to compile { \$templateName }</h1>
<h2>Error message:</h2>
<p>{\$message}</p>
<h2>Template source:</h2>
<pre><code><ol start=\"{\$firstLine + 1}\">
{for \$lineNo: \$line in \$lines}
    <li>
    {if \$lineNo = \$errorLine}
        <b>{\$line}</b>
    {else}
        {\$line}
    {endif}
    </li>
{{$closingTagPrefix}for}
</ol></code></pre>{{$closingTagPrefix}block}";

        return strtr($source, array("\n" => ''));
    }
}
