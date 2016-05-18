<?php

namespace mindplay\kissform\Fields;

use mindplay\kissform\Field;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\Validators\CheckAccept;

/**
 * This class represents a labeled checkbox structure, e.g. `div.checkbox` with an `input` and
 * matching `label` tag inside.
 *
 * Note that the markup deviates from Bootstrap standard markup, which isn't useful for styling.
 *
 * @link https://github.com/twbs/bootstrap/issues/19931
 * @link https://github.com/flatlogic/awesome-bootstrap-checkbox
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

        $id = $renderer->getId($this);
        
        $input = $renderer->tag(
            'input',
            $attr + [
                'name'    => $renderer->getName($this),
                'id'      => $id,
                'value'   => $this->checked_value,
                'checked' => $model->getInput($this) === $this->checked_value,
                'type'    => 'checkbox',
            ]
        );

        return
            ($this->wrapper_class ? '<div class="' . $this->wrapper_class . '">' : '')
            . $input
            . ($label ? $renderer->tag('label', ['for' => $id], $renderer->softEscape($label)) : '')
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
