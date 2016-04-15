<?php

namespace mindplay\kissform\Fields;

use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;

/**
 * This class represents an HTML <input type="password"> element.
 *
 * Note that, for security reasons, this Field type never echoes back it's value
 * when rendered.
 */
class PasswordField extends TextField
{
    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        $attr['value'] = '';

        return $renderer->inputFor(
            $this,
            'password',
            $renderer->mergeAttrs(['maxlength' => $this->max_length], $attr)
        );
    }
}
