<?php

namespace mindplay\kissform;

/**
 * This class represents an <input type="email"> element.
 */
class EmailField extends TextField
{
    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        return $renderer->buildInput($this, 'email', $attr + array('maxlength' => $this->max_length));
    }
}
