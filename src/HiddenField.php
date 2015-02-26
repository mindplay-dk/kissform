<?php

namespace mindplay\kissform;

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
            $attr + array(
                'type' => 'hidden',
                'name' => $renderer->createName($this),
                'value' => $model->getInput($this),
            )
        );
    }
}
