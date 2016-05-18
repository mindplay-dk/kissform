<?php

namespace mindplay\kissform\Fields;

use mindplay\kissform\Field;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\Validators\CheckSelected;

/**
 * This class provides information about a group of `<input type="radio">` elements.
 *
 * Note that the markup deviates from Bootstrap standard markup, which isn't useful for styling.
 *
 * @link https://github.com/twbs/bootstrap/issues/19931
 * @link https://github.com/flatlogic/awesome-bootstrap-checkbox
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

            $id = $renderer->getId($this, $value);

            $group = $renderer->tag(
                'input',
                $renderer->mergeAttrs(
                    [
                        'type'    => 'radio',
                        'id'      => $id,
                        'name'    => $renderer->getName($this),
                        'value'   => $value,
                        'checked' => $checked,
                    ],
                    $this->input_attr,
                    $attr
                )
            );

            $group .= $renderer->tag('label', $this->label_attr + ['for' => $id], $renderer->softEscape($label));
            
            if ($this->wrapper_tag) {
                $group = $renderer->tag($this->wrapper_tag, $this->wrapper_attr, $group);
            }

            $html .= $group;
        }

        return $html;
    }
}
