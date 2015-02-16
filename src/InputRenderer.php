<?php

namespace mindplay\kissform;

use RuntimeException;

/**
 * This class renders and populates input elements for use in forms,
 * by consuming property-information provided by a type-descriptor.
 */
class InputRenderer
{
    /**
     * @var string HTML encoding charset
     */
    public $encoding = 'UTF-8';

    /**
     * @var array form input (maps of strings, possibly nested)
     */
    public $input;

    /**
     * @var string[] map where field name => error message
     */
    public $errors = array();

    /**
     * @var string form element name-attribute prefix
     */
    public $name_prefix;

    /**
     * @var string|null form element id-attribute prefix (or null, to bypass id-attribute generation)
     */
    public $id_prefix;

    /**
     * @var string CSS class name applied to all form controls
     *
     * @see buildInput()
     */
    public $input_class = 'form-control';

    /**
     * @var string CSS class name added to labels
     *
     * @see label()
     */
    public $label_class = 'control-label';

    /**
     * @var string CSS class-name added to required fields
     *
     * @see group()
     */
    public $required_class = 'is-required';

    /**
     * @var string CSS class-name added to fields with error state
     *
     * @see group()
     */
    public $error_class = 'has-error';

    /**
     * @var string group tag name (e.g. "div", "fieldset", etc.; defaults to "div")
     *
     * @see group()
     * @see endGroup()
     */
    public $group_tag = 'div';

    /**
     * @var string[] default attributes to be added to opening control-group tags
     *
     * @see group()
     */
    public $group_attrs = array('class' => 'form-group');

    /**
     * @var string[] map of attributes to apply to date-picker inputs
     *
     * @see FormHelper::date()
     */
    public $date_attrs = array(
        'readonly' => 'readonly',
        'data-ui' => 'datepicker',
    );

    /**
     * @var string[] map of attributes to apply to date/time-picker inputs
     *
     * @see FormHelper::datetime()
     */
    public $datetime_attrs = array(
        'readonly' => 'readonly',
        'data-ui' => 'datetimepicker',
    );

    /**
     * @param array  $input       form input: maps of strings, possibly nested (for example $_GET or $_POST)
     * @param string $name_prefix base name for inputs, e.g. 'myform' or 'myform[123]', etc.
     * @param null   $id_prefix   base id for inputs, e.g. 'myform' or 'myform-123', etc.
     */
    public function __construct(array $input, $name_prefix = null, $id_prefix = null)
    {
        $this->input = $input;
        $this->name_prefix = $name_prefix;
        $this->id_prefix = $id_prefix === null
            ? preg_replace('/\W/', '', $this->name_prefix)
            : $id_prefix;
    }

    /**
     * @param Field $field
     *
     * @return string|array|null value (or NULL, if no value exists in $input)
     */
    protected function getInput(Field $field)
    {
        if (!isset($this->input[$field->name])) {
            return null;
        }

        if (is_scalar($this->input[$field->name])) {
            return (string)$this->input[$field->name];
        }

        return $this->input[$field->name];
    }

    /**
     * @param Field $field
     *
     * @return string
     *
     * @see Field::$label
     */
    protected function getLabel(Field $field)
    {
        return $field->label;
    }

    /**
     * @param Field $field
     *
     * @return string
     *
     * @see Field::$placeholder
     */
    protected function getPlaceholder(Field $field)
    {
        return $field->placeholder;
    }

    /**
     * @param Field $field
     *
     * @return bool true, if the given Field is required
     */
    protected function isRequired(Field $field)
    {
        return $field->required;
    }

    /**
     * @param Field $field
     *
     * @return bool true, if the given Field has an error message
     *
     * @see $errors
     */
    protected function hasError(Field $field)
    {
        return isset($this->errors[$field->name]);
    }

    /**
     * @param Field $field
     *
     * @return string|null computed name-attribute
     */
    protected function createName(Field $field)
    {
        return $this->name_prefix
            ? $this->name_prefix . '[' . $field->name . ']'
            : $field->name;
    }

    /**
     * @param Field $field
     *
     * @return string|null computed id-attribute
     */
    protected function createId(Field $field)
    {
        return $this->id_prefix
            ? $this->id_prefix . '-' . $field->name
            : null;
    }

    /**
     * Build an HTML tag
     *
     * @param string   $name  HTML tag name
     * @param string[] $attr  map of HTML attributes
     * @param bool     $close true to build a self-closing tag
     *
     * @return string
     */
    protected function buildTag($name, array $attr, $close)
    {
        $html = '<' . $name;

        ksort($attr);

        foreach ($attr as $name => $value) {
            if ($value === null) {
                continue; // skip NULL attributes
            }

            if (is_array($value)) {
                $value = implode(' ', $value); // implode multi-value (e.g. class-names)
            }

            $html .= ' ' . $name . '="' . htmlspecialchars($value, ENT_COMPAT, $this->encoding) . '"';
        }

        $html .= $close
            ? '/>'
            : '>';

        return $html;
    }

    /**
     * Build an HTML input-tag
     *
     * @param Field    $field
     * @param string   $type HTML input type-attribute (e.g. "text", "password", etc.)
     * @param string[] $attr map of HTML attributes
     *
     * @return string
     */
    protected function buildInput(Field $field, $type, array $attr = array())
    {
        $attr['class'] = isset($attr['class'])
            ? array_merge(array($this->input_class), (array)$attr['class'])
            : $this->input_class;

        return $this->buildTag(
            'input',
            $attr + array(
                'name' => $this->createName($field),
                'id' => $this->createId($field),
                'value' => $this->getInput($field),
                'type' => $type,
                'placeholder' => @$attr['placeholder'] ?: $this->getPlaceholder($field),
            ),
            true
        );
    }

    /**
     * Build an HTML opening tag for an input group, with CSS classes added for
     * {@see Field::$required} and error state, as needed.
     *
     * Call {@link endGroup()} to create the matching closing tag.
     *
     * @param Field    $field
     * @param string[] $attr map of HTML attributes (optional)
     *
     * @return string
     *
     * @see $group_tag
     * @see $group_class
     * @see $required_class
     * @see $error_class
     * @see endGroup()
     */
    public function group(Field $field, array $attr = array())
    {
        $classes = isset($this->group_attrs['class'])
            ? (array) $this->group_attrs['class']
            : array();

        if ($this->required_class !== null && $this->isRequired($field)) {
            $classes[] = $this->required_class;
        }

        if ($this->error_class !== null && $this->hasError($field)) {
            $classes[] = $this->error_class;
        }

        $attr['class'] = isset($attr['class'])
            ? array_merge($classes, (array)$attr['class'])
            : $classes;

        return $this->buildTag($this->group_tag, $attr, false);
    }

    /**
     * Returns the matching closing tag for a {@link group()} tag.
     *
     * @return string
     *
     * @see group()
     * @see $group_tag
     */
    public function endGroup()
    {
        return "</{$this->group_tag}>";
    }

    /**
     * Build an HTML <input type="text" /> tag
     *
     * @param TextField $field
     * @param string[]  $attr map of HTML attributes
     *
     * @return string
     */
    public function text(TextField $field, array $attr = array())
    {
        return $this->buildInput($field, 'text', array_merge(array('maxlength' => $field->max_length), $attr));
    }

    /**
     * Build an HTML <textarea> tag
     *
     * @param TextField $field
     * @param array     $attr
     *
     * @return string
     */
    public function textarea(TextField $field, array $attr = array())
    {
        $name = $field->name;

        $attr += array(
            'name' => $this->createName($field),
            'id' => $this->createId($field),
            'placeholder' => @$attr['placeholder'] ?: $this->getPlaceholder($field),
        );

        $attr['class'] = isset($attr['class'])
            ? array_merge(array($this->input_class), (array)$attr['class'])
            : $this->input_class;

        return $this->buildTag(
            'textarea',
            $attr,
            false
        ) . htmlspecialchars($this->getInput($field), ENT_COMPAT, $this->encoding) . '</textarea>';
    }

    /**
     * Build an HTML <input type="password" /> tag
     *
     * @param TextField $field
     * @param string[]  $attr map of HTML attributes
     *
     * @return string
     */
    public function password(TextField $field, array $attr = array())
    {
        return $this->buildInput($field, 'password', $attr + array('maxlength' => $field->max_length));
    }

    /**
     * Build an HTML <input type="hidden" /> tag
     *
     * @param TextField $field
     * @param string[]  $attr map of HTML attributes
     *
     * @return string
     */
    public function hidden(TextField $field, array $attr = array())
    {
        return $this->buildInput($field, 'hidden', $attr);
    }

    /**
     * Build an HTML5 <input type="email" /> tag
     *
     * @param TextField $field
     * @param string[]  $attr map of HTML attributes
     *
     * @return string
     */
    public function email(TextField $field, array $attr = array())
    {
        return $this->buildInput($field, 'email', $attr + array('maxlength' => $field->max_length));
    }

    /**
     * Build an HTML <label for="id" /> tag
     *
     * @param Field       $field
     * @param string|null $label label text
     * @param array       $attr  map of HTML attributes
     *
     * @return string
     *
     * @see Field::$label
     */
    public function label(Field $field, $label = null, array $attr = array())
    {
        $attr['class'] = isset($attr['class'])
            ? array_merge(array($this->label_class), (array)$attr['class'])
            : $this->label_class;

        $id = $this->createId($field);

        if ($id === null) {
            throw new RuntimeException("cannot produce a label when FormHelper::\$id_prefix is NULL");
        }

        if ($label === null) {
            $label = $this->getLabel($field);

            if ($label === null) {
                return ''; // no label available
            }
        }

        return $this->buildTag(
            'label',
            $attr + array(
                'for' => $id,
            ),
            false
        ) . htmlspecialchars($label, ENT_COMPAT, $this->encoding, false) . '</label>';
    }

    /**
     * Build a labeled checkbox structure, e.g. span.checkbox > label > input
     *
     * @param BoolField   $field
     * @param string|null $label label HTML
     * @param string      $value value indicating
     *
     * @return string
     *
     * @see Field::$label
     */
    public function checkbox(BoolField $field, $label = null, $value = null)
    {
        $checked_value = $value ?: $field->checked_value;

        return
            '<div class="checkbox"><label>'
            . $this->buildTag(
                'input',
                array(
                    'name' => $this->createName($field),
                    'value' => $checked_value,
                    'checked' => $this->getInput($field) == $checked_value ? 'checked' : null,
                    'type' => 'checkbox',
                ),
                true
            )
            . htmlspecialchars($label ?: $this->getLabel($field), ENT_COMPAT, $this->encoding, false)
            . '</label></div>';
    }

    /**
     * Build a <select> tag with a set of <option> tags corresponding to values.
     *
     * @param Field|HasOptions $field
     * @param string[]|null    $options hash where option values => option labels (optional; defaults to $field->options)
     * @param array            $attr    map of HTML attributes (for the select tag)
     *
     * @return string
     *
     * @see HasOptions
     * @see EnumField::$options
     */
    public function select(Field $field, $options = null, array $attr = array())
    {
        $html = $this->buildTag(
            'select',
            $attr + array(
                'name' => $this->createName($field),
                'id' => $this->createId($field),
            ),
            false
        );

        $selected = $this->getInput($field);

        if ($options === null && $field instanceof HasOptions) {
            $options = $field->getOptions();
        }

        foreach ($options as $value => $label) {
            $equal = is_numeric($selected)
                ? $value == $selected // loose comparison works well for NULLs and numbers
                : $value === $selected; // strict comparison for everything else

            $html .= '<option value="' . htmlspecialchars($value, ENT_COMPAT, $this->encoding) . '"'
                . ($equal ? ' selected="selected"' : '') . '>'
                . htmlspecialchars($label, ENT_COMPAT, $this->encoding) . '</option>';
        }

        return $html . '</select>';
    }

    /**
     * Build a text input intended for use with a date picker on the client-side
     *
     * @param TextField $field
     * @param array     $attr map of HTML attributes (for the div container)
     *
     * @return string
     */
    public function date(TextField $field, array $attr = array())
    {
        return $this->text(
            $field,
            $attr + $this->date_attrs
        );
    }

    /**
     * Build a text input intended for use with a date/time picker on the client-side
     *
     * @param TextField $field
     * @param array     $attr map of HTML attributes (for the div container)
     *
     * @return string
     */
    public function datetime(TextField $field, array $attr = array())
    {
        return $this->text(
            $field,
            $attr + $this->datetime_attrs
        );
    }
}
