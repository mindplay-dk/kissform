<?php

namespace mindplay\kissform;

/**
 * This class represents a labeled checkbox structure, e.g. span.checkbox > label > input
 */
class CheckboxField extends Field implements RenderableField
{
    /**
     * @var string
     */
    public $checked_value = '1';

    /**
     * @var string overrides the default checkbox label (provided by Field::$label)
     */
    public $label;

    /**
     * @var string|null wrapper class-name (or NULL to disable the wrapper div; defaults to "checkbox")
     */
    public $wrapper_class = 'checkbox';

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        $label = $this->label ?: $renderer->getLabel($this);

        $input = $renderer->tag(
            'input',
            $attr + array(
                'name'    => $renderer->createName($this),
                'value'   => $this->checked_value,
                'checked' => $model->getInput($this) === $this->checked_value,
                'type'    => 'checkbox',
            )
        );

        return
            ($this->wrapper_class ? '<div class="' . $this->wrapper_class . '">' : '')
            . ($label ? $renderer->tag('label', array(), $input . $renderer->softEncode($label)) : $input)
            . ($this->wrapper_class ? '</div>' : '');
    }

    /**
     * @param InputModel $model
     *
     * @return bool
     */
    public function getValue(InputModel $model)
    {
        return $model->getInput($this) === $this->checked_value;
    }

    /**
     * @param InputModel $model
     * @param bool       $value
     *
     * @return void
     */
    public function setValue(InputModel $model, $value)
    {
        $model->setInput($this, $value ? $this->checked_value : null);
    }
}
