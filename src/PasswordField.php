<?php

namespace mindplay\kissform;

/**
 * This class represents an HTML <input type="password"> element.
 */
class PasswordField extends TextField
{
    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        return $renderer->buildInput($this, 'password', $attr + array('maxlength' => $this->max_length));
    }
}
