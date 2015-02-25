<?php

namespace mindplay\kissform;

/**
 * This class provides information about a <select> input and available options.
 */
class SelectField extends Field implements RenderableField, HasOptions
{
    /**
     * @var string[] map where option values map to option labels
     */
    protected $options;

    /**
     * @param string $name field name
     * @param array  $options
     */
    public function __construct($name, array $options)
    {
        parent::__construct($name);

        $this->options = $options;
    }

    /**
     * @see HasOptions::getOptions()
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        $html = $renderer->buildTag(
            'select',
            $attr + array(
                'name' => $renderer->createName($this),
                'id' => $renderer->createId($this),
            ),
            false
        );

        $selected = $model->getInput($this);

        $options = $this->getOptions();

        foreach ($options as $value => $label) {
            $equal = is_numeric($selected)
                ? $value == $selected // loose comparison works well for NULLs and numbers
                : $value === $selected; // strict comparison for everything else

            $html .= '<option value="' . $renderer->encode($value) . '"'
                . ($equal ? ' selected="selected"' : '') . '>'
                . $renderer->encode($label) . '</option>';
        }

        return $html . '</select>';
    }
}
