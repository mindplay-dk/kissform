<?php

namespace mindplay\kissform;

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
        $attr += array(
            'name' => $renderer->createName($this),
            'id' => $renderer->createId($this),
            'placeholder' => @$attr['placeholder'] ?: $renderer->getPlaceholder($this),
        );

        $attr['class'] = isset($attr['class'])
            ? array_merge(array($renderer->input_class), (array)$attr['class'])
            : $renderer->input_class;

        return $renderer->buildTag(
            'textarea',
            $attr,
            false
        ) . $renderer->encode($model->getInput($this)) . '</textarea>';
    }
}
