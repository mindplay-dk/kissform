<?php

namespace mindplay\kissform\Fields;

use mindplay\kissform\Field;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\Validators\CheckAccept;

/**
 * This class represents a labeled checkbox structure, e.g. span.checkbox > label > input
 */
class CheckboxField extends Field
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
        $label = $renderer->getLabel($this);

        $input = $renderer->tag(
            'input',
            $attr + [
                'name'    => $renderer->getName($this),
                'value'   => $this->checked_value,
                'checked' => $model->getInput($this) === $this->checked_value,
                'type'    => 'checkbox',
            ]
        );

        return
            ($this->wrapper_class ? '<div class="' . $this->wrapper_class . '">' : '')
            . ($label ? $renderer->tag('label', [], $input . $renderer->softEscape($label)) : $input)
            . ($this->wrapper_class ? '</div>' : '');
    }

    /**
     * @param InputModel $model
     *
     * @return bool
     *
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
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

    public function createValidators()
    {
        return [new CheckAccept($this->checked_value)];
    }
}
