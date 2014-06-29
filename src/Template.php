<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty;

use Minty\Compiler\Exceptions\TemplatingException;

abstract class Template
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var bool|string The name of the parent template if there is one
     */
    private $parentTemplate = false;

    /**
     * @var Template
     */
    private $parentOf;

    /**
     * @var array
     */
    private $blocks = array();

    /**
     * @var string
     */
    private $templateName;

    /**
     * @var string
     */
    private $extension;

    public function __construct(Environment $environment, $template, array $blocks)
    {
        $this->environment  = $environment;
        $this->templateName = $template;

        $dot = strrpos($template, '.');
        if ($dot !== false) {
            $this->extension = substr($template, $dot + 1);
        } else {
            $this->extension = '';
        }
        foreach ($blocks as $block) {
            $this->blocks[$block] = array($this, 'block_' . $block);
        }
    }

    public function importBlocks($source, $blocks = null)
    {
        $sourceTemplate = $this->environment->load($source);
        if ($blocks === null) {
            $blocks = array_keys($sourceTemplate->blocks);
        }
        foreach ((array)$blocks as $name => $block) {
            $targetBlockName = is_int($name) ? $block : $name;
            if (!isset($sourceTemplate->blocks[$targetBlockName])) {
                throw new TemplatingException("Block {$targetBlockName} is not found in template {$source}");
            }
            if (isset($this->blocks[$block])) {
                throw new TemplatingException("Block {$block} is already present in template {$this->templateName}");
            }
            $this->blocks[$block] = array($sourceTemplate, 'block_' . $targetBlockName);
        }
    }

    public function __get($key)
    {
        switch ($key) {
            case 'template':
                return $this->templateName;
            case 'extension':
                return $this->extension;
            case 'parent':
                if (!isset($this->parentTemplate)) {
                    throw new \OutOfBoundsException("Property {$key} is not set.");
                }

                return $this->environment->load($this->parentTemplate);

            default:
                throw new \OutOfBoundsException("Property {$key} is not set.");
        }
    }

    public function __isset($key)
    {
        switch ($key) {
            case 'template':
            case 'extension':
                return true;
            case 'parent':
                return isset($this->parentTemplate);
            default:
                return false;
        }
    }

    protected function setParentTemplate($parentTemplate)
    {
        $this->parentTemplate = $parentTemplate;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function renderBlock($blockName, Context $context, $parentBlock = false)
    {
        $base = $this;
        if (!$parentBlock) {
            while ($base->parentOf) {
                $base = $base->parentOf;
            }
        }
        $blockPresent = isset($base->blocks[$blockName]);
        if ($parentBlock || !$blockPresent) {
            if ($this->renderParentBlock($base, $blockName, $context)) {
                return;
            }
        }
        if ($blockPresent) {
            call_user_func($base->blocks[$blockName], $context);
        } else {
            throw new \RuntimeException("Block {$blockName} was not found.");
        }
    }

    public function displayTemplate(Context $context)
    {
        //if this method is called the template must extend an other
        $parent = $this->environment->load($this->parentTemplate);

        $oldParentOf      = $parent->parentOf;
        $parent->parentOf = $this;

        $parent->displayTemplate($context);

        $parent->parentOf = $oldParentOf;
    }

    /**
     * @param Template $parent
     * @param          $blockName
     * @param Context  $context
     *
     * @return bool
     */
    private function renderParentBlock(Template $parent, $blockName, Context $context)
    {
        while ($parent->parentTemplate) {
            $parent = $this->environment->load($parent->parentTemplate);
            if (isset($parent->blocks[$blockName])) {
                call_user_func($parent->blocks[$blockName], $context);

                return true;
            }
        }

        return false;
    }
}
