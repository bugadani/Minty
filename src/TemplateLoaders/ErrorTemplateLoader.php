<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\TemplateLoaders;

use Minty\Environment;

class ErrorTemplateLoader extends StringLoader
{

    /**
     * @inheritdoc
     */
    public function setEnvironment(Environment $environment)
    {
        parent::setEnvironment($environment);
        $this->addTemplate(
            '__compile_error_template',
            $this->getCompileErrorTemplate(
                $environment->getOption('delimiters'),
                $environment->getOption('block_end_prefix')
            )
        );
    }

    private function getCompileErrorTemplate(array $delimiters, $closingTagPrefix)
    {
        list($tagOpen, $tagClose) = $delimiters['tag'];
        $source = "{$tagOpen}block error{$tagClose}
{$tagOpen} \$firstLine: max(\$exception.getSourceLine() - 2, 1) {$tagClose}
{$tagOpen} \$highlightedLine: \$exception.getSourceLine() - \$firstLine {$tagClose}
<h1>Failed to compile {$tagOpen} \$templateName {$tagClose}</h1>
<h2>Error message:</h2>
<p>{$tagOpen} \$exception.getMessage() {$tagClose}</p>
<h2>Template source:</h2>
<pre><code><ol start=\"{ \$firstLine }\">
{$tagOpen} for \$lineNo: \$line in \$templateName|source|split('\\n')|slice(\$firstLine - 1, 7) {$tagClose}
    <li>
    {$tagOpen} if \$lineNo = \$highlightedLine {$tagClose}
        <b>{$tagOpen}\$line{$tagClose}</b>
    {$tagOpen} else }
        {$tagOpen} \$line {$tagClose}
    {$tagOpen} {$closingTagPrefix}if {$tagClose}
    </li>
{$tagOpen} {$closingTagPrefix}for {$tagClose}
</ol></code></pre>
{$tagOpen} {$closingTagPrefix}block {$tagClose}";

        return strtr($source, ["\r\n" => '', "\r" => '', "\n" => '']);
    }
}
