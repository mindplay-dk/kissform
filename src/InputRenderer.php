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
     * @var bool if true, use long form XHTML for value-less attributes (e.g. disabled="disabled")
     *
     * @see attrs()
     */
    public $xhtml = false;

    /**
     * @var InputModel input model
     */
    public $model;

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
     * @param InputModel|array|null $model       input model, or (possibly nested) input array (e.g. $_GET or $_POST)
     * @param string                $name_prefix base name for inputs, e.g. 'myform' or 'myform[123]', etc.
     * @param null                  $id_prefix   base id for inputs, e.g. 'myform' or 'myform-123', etc.
     */
    public function __construct($model = null, $name_prefix = null, $id_prefix = null)
    {
        $this->model = InputModel::create($model);
        $this->name_prefix = $name_prefix;
        $this->id_prefix = $id_prefix === null
            ? preg_replace('/\W/', '', $this->name_prefix)
            : $id_prefix;
    }

    /**
     * @param Field $field
     *
     * @return string
     *
     * @see Field::$label
     */
    public function getLabel(Field $field)
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
    public function getPlaceholder(Field $field)
    {
        return $field->placeholder;
    }

    /**
     * @param Field $field
     *
     * @return bool true, if the given Field is required
     */
    public function isRequired(Field $field)
    {
        return $field->required;
    }

    /**
     * @param Field $field
     *
     * @return string|null computed name-attribute
     */
    public function createName(Field $field)
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
    public function createId(Field $field)
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
    public function buildTag($name, array $attr, $close)
    {
        return '<' . $name . $this->attrs($attr) . ($close ? '/>' : '>');
    }

    /**
     * Build HTML attributes for use inside an HTML (or XML) tag.
     *
     * Includes a leading space, since this is usually used inside a tag, e.g.:
     *
     *     <div<?= $form->attrs(array('class' => 'foo')) ?>>...</div>
     *
     * Accepts strings, or arrays of strings, as attribute-values - arrays will
     * be folded uses space as a separator, e.g. useful for the class-attribute.
     *
     * Attributes containing NULL, FALSE or an empty array() are ignored.
     *
     * Attributes containing TRUE are rendered as value-less attributes.
     *
     * @param array $attr map where attribute-name => attribute value(s)
     * @param bool $sort true, to sort attributes by name; otherwise false (sorting is enabled by default)
     *
     * @return string
     */
    public function attrs(array $attr, $sort = true)
    {
        if ($sort) {
            ksort($attr);
        }

        $html = '';

        foreach ($attr as $name => $value) {
            if ($value === array() || $value === null || $value === false) {
                continue; // skip NULL and FALSE attributes and empty arrays
            }

            if (is_array($value)) {
                $value = implode(' ', $value); // fold multi-value attribute (e.g. class-names)
            }

            if ($value === true) {
                $html .= $this->xhtml ?
                    ' ' . $name . '="' . $name . '"' // e.g. disabled="disabled" (as required for XHTML)
                    : ' ' . $name; // value-less HTML attribute
            } else {
                $html .= ' ' . $name . '="' . $this->encode($value) . '"';
            }
        }

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
    public function buildInput(Field $field, $type, array $attr = array())
    {
        $attr['class'] = isset($attr['class'])
            ? array_merge(array($this->input_class), (array)$attr['class'])
            : $this->input_class;

        return $this->buildTag(
            'input',
            $attr + array(
                'name'        => $this->createName($field),
                'id'          => $this->createId($field),
                'value'       => $this->model->getInput($field),
                'type'        => $type,
                'placeholder' => @$attr['placeholder'] ?: $this->getPlaceholder($field),
            ),
            true
        );
    }

    /**
     * Encode plain text as HTML
     *
     * @param string $text plain text
     * @param int    $flags encoding flags (optional, see htmlspecialchars)
     *
     * @return string escaped HTML
     *
     * @see softEncode()
     */
    public function encode($text, $flags = ENT_COMPAT)
    {
        return htmlspecialchars($text, $flags, $this->encoding, true);
    }

    /**
     * Encode plain text as HTML, while attempting to avoid double-encoding
     *
     * @param string $text plain text
     * @param int    $flags encoding flags (optional, see htmlspecialchars)
     *
     * @return string escaped HTML
     *
     * @see encode()
     */
    public function softEncode($text, $flags = ENT_COMPAT)
    {
        return htmlspecialchars($text, $flags, $this->encoding, false);
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
            ? (array)$this->group_attrs['class']
            : array();

        if ($this->required_class !== null && $this->isRequired($field)) {
            $classes[] = $this->required_class;
        }

        if ($this->error_class !== null && $this->model->hasError($field)) {
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
     * Build an HTML input for any given Field.
     *
     * @param Field $field
     * @param array $attr
     *
     * @return string
     */
    public function input(Field $field, array $attr = array())
    {
        // TODO implement template selection and support for external templates

        if ($field instanceof RenderableField) {
            return $field->renderInput($this, $this->model, $attr);
        }

        throw new RuntimeException("no input-view available for the given Field");
    }

    /**
     * Shortcut function, builds an HTML group cotaining a label and input.
     *
     * @param Field       $field
     * @param string|null $label      label text (optional)
     * @param array       $input_attr map of HTML attributes for the input (optional)
     * @param array       $group_attr map of HTML attributes for the group (optional)
     *
     * @return string
     */
    public function inputGroup(Field $field, $label = null, array $input_attr = array(), $group_attr = array())
    {
        return $this->group($field, $group_attr)
            . $this->label($field)
            . $this->input($field, $input_attr)
            . $this->endGroup();
    }

    /**
     * Build an HTML <label for="id" /> tag
     *
     * @param Field       $field
     * @param string|null $label label text (optional)
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
}
