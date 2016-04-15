<?php

namespace mindplay\kissform\Fields;

use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;

/**
 * This class represents an HTML <textarea> element.
 */
class TextArea extends TextField
{
    /**
     * @var int|null visible number of lines in the textarea
     */
    public $rows = null;

    /**
     * @var int|null visible width of the textarea
     */
    public $cols = null;

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        $attr += [
            'name' => $renderer->getName($this),
            'id' => $renderer->getId($this),
            'placeholder' => @$attr['placeholder'] ?: $renderer->getPlaceholder($this),
        ];

        $attr['class'] = isset($attr['class'])
            ? array_merge([$renderer->input_class], (array) $attr['class'])
            : $renderer->input_class;

        return $renderer->tag(
            'textarea',
            $attr,
            $renderer->escape($model->getInput($this))
        );
    }
}
