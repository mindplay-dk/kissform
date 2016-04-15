<?php

namespace mindplay\kissform\Fields;

use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\Validators\CheckEmail;

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
        return $renderer->inputFor($this, 'email', $attr + ['maxlength' => $this->max_length]);
    }

    /**
     * {@inheritdoc}
     */
    public function createValidators()
    {
        $validators = parent::createValidators();

        $validators[] = new CheckEmail();

        return $validators;
    }
}
