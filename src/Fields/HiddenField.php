<?php

namespace mindplay\kissform\Fields;

use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;

/**
 * This class represents an <input type="hidden"> element
 */
class HiddenField extends TextField
{
    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        return $renderer->tag(
            'input',
            $attr + [
                'type' => 'hidden',
                'name' => $renderer->getName($this),
                'value' => $model->getInput($this),
            ]
        );
    }
}
