<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

abstract class Template
{
    /**
     * @var TemplateLoader
     */
    private $loader;

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
    private $blocks;

    /**
     * @var string
     */
    private $templateName;

    public function __construct(
        TemplateLoader $loader,
        Environment $environment,
        $template,
        array $blocks
    ) {
        $this->loader       = $loader;
        $this->environment  = $environment;
        $this->templateName = $template;
        $this->blocks       = $blocks;
    }

    public function __get($key)
    {
        switch ($key) {
            case 'template':
                return $this->templateName;
            case 'parent':
                return $this->parentTemplate;

            default:
                throw new \OutOfBoundsException("Property {$key} is not set.");
        }
    }

    protected function setParentTemplate($parentTemplate)
    {
        $this->parentTemplate = $parentTemplate;
    }

    public function getLoader()
    {
        return $this->loader;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function getExtension($name)
    {
        return $this->environment->getExtension($name);
    }

    public function hasMethod($object, $method)
    {
        if (!is_object($object)) {
            throw new \UnexpectedValueException('Variable is not an object.');
        }

        return method_exists($object, $method);
    }

    public function renderBlock($blockName, Context $context, $parentBlock = false)
    {
        if ($this->parentOf) {
            $base = $this->parentOf;
        } else {
            $base = $this;
        }
        $blockPresent = in_array($blockName, $base->blocks);
        if ($parentBlock || !$blockPresent) {
            $this->renderParentBlock($base, $blockName, $context);
        } elseif ($blockPresent) {
            $base->{'block_' . $blockName}($context);
        } else {
            throw new \RuntimeException("Block {$blockName} was not found.");
        }
    }

    public function displayTemplate(Context $context)
    {
        //if this method is called the template must extend an other
        $parent = $this->loader->load($this->parentTemplate);

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
     * @throws \RuntimeException
     */
    private function renderParentBlock(Template $parent, $blockName, Context $context)
    {
        while ($parent->parentTemplate) {
            $parent = $this->loader->load($parent->parentTemplate);
            if (in_array($blockName, $parent->blocks)) {
                $parent->{'block_' . $blockName}($context);

                return;
            }
        }
        throw new \RuntimeException("Parent for block '{$blockName}' was not found.");
    }
}
