<?php

namespace mindplay\kissform\Fields;

use mindplay\kissform\Field;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\Validators\CheckSelected;

/**
 * This class provides information about a <select> input and available options.
 */
class SelectField extends Field
{
    /**
     * @var string[] map where option values map to option labels
     */
    protected $options;

    /**
     * @var string label of disabled first option (often directions or a description)
     */
    public $disabled;

    /**
     * @param string   $name    field name
     * @param string[] $options map where option values map to option labels
     */
    public function __construct($name, array $options)
    {
        parent::__construct($name);

        $this->options = $options;
    }

    /**
     * @inheritdoc
     */
    public function createValidators()
    {
        return [new CheckSelected(array_keys($this->options))];
    }

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        $selected = $model->getInput($this);

        $values = array_map('strval', array_keys($this->options));

        if (! in_array($selected, $values, true)) {
            $selected = null; // selected value isn't present in the list of options
        }

        $html = '';

        if ($this->disabled !== null) {
            $html .= '<option' . $renderer->attrs(['disabled' => true, 'selected' => ($selected == '')]) . '>'
                . $renderer->escape($this->disabled) . '</option>';
        }

        foreach ($this->options as $value => $label) {
            $equal = is_numeric($selected)
                ? $value == $selected // loose comparison works well for NULLs and numbers
                : $value === $selected; // strict comparison for everything else

            $html .= '<option' . $renderer->attrs(['value' => $value, 'selected' => $equal]) . '>'
                . $renderer->escape($label) . '</option>';
        }

        return $renderer->tag(
            'select',
            $renderer->mergeAttrs(
                [
                    'name' => $renderer->getName($this),
                    'id' => $renderer->getId($this),
                    'class' => $renderer->input_class,
                ],
                $attr
            ),
            $html
        );
    }
}
