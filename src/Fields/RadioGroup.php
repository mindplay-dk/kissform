<?php

namespace mindplay\kissform\Fields;

use mindplay\kissform\Field;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\Validators\CheckSelected;

/**
 * This class provides information about a group of <input type="radio"> elements.
 */
class RadioGroup extends Field
{
    /**
     * @var string[] map where option values map to option labels
     */
    protected $options;

    /**
     * @var string[] map of HTML attributes for the <input> tag
     */
    public $input_attr = [];

    /**
     * @var string[] map of HTML attributes for the <label> tag
     */
    public $label_attr = [];

    /**
     * @var string|null wrapper tag (e.g. "div", or NULL to disable wrapper-tags)
     */
    public $wrapper_tag = 'div';

    /**
     * @var string[] map of HTML attributes for the wrapper-tag around each checkbox group
     */
    public $wrapper_attr = ['class' => 'radio'];

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

        $html = '';

        foreach ($this->options as $value => $label) {
            $checked = is_numeric($selected)
                ? $value == $selected // loose comparison works well for NULLs and numbers
                : $value === $selected; // strict comparison for everything else

            $tag = $renderer->tag(
                'input',
                $renderer->mergeAttrs(
                    [
                        'type' => 'radio',
                        'name' => $renderer->getName($this),
                        'value' => $value,
                        'checked' => $checked
                    ],
                    $this->input_attr,
                    $attr
                )
            );

            $tag = $renderer->tag('label', $this->label_attr, $tag . ' ' . $renderer->escape($label));

            if ($this->wrapper_tag) {
                $tag = $renderer->tag($this->wrapper_tag, $this->wrapper_attr, $tag);
            }

            $html .= $tag;
        }

        return $html;
    }
}
