<?php

namespace mindplay\kissform;

/**
 * This class provides information about a text field, e.g. a plain
 * input type=text element.
 */
class TextField extends Field implements RenderableField
{
    /** @var int|null */
    public $min_length;

    /** @var int|null */
    public $max_length;

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        return $renderer->buildInput($this, 'text', array_merge(array('maxlength' => $this->max_length), $attr));
    }
}
