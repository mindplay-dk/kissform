<?php

namespace mindplay\kissform;

use mindplay\kissform\Facets\FieldInterface;
use RuntimeException;

/**
 * This class renders and populates input elements for use in forms,
 * by consuming property-information provided by {@see Field} objects,
 * and populating them with state from an {@see InputModel}.
 *
 * Conventions for method-names in this class:
 *
 *   * `get_()` and `is_()` methods provide raw information about Fields
 * 
 *   * `render_()` methods delegate rendering to {@see Field::renderInput} implementations.
 * 
 *   * `_For()` methods (such as `inputFor()`) render low-level HTML tags (with state) for Fields
 * 
 *   * Verb methods like `visit`, `merge` and `escape` perform various relevant actions
 * 
 *   * Noun methods like `tag`, `attrs` and `label` create low-level HTML tags
 *
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
     * @var string|string[]|null form element collection name(s)
     */
    public $collection_name;

    /**
     * @var string|null form element id-attribute prefix (or null, to bypass id-attribute generation)
     */
    public $id_prefix;

    /**
     * @var string CSS class name applied to all form controls
     *
     * @see inputFor()
     */
    public $input_class = 'form-control';

    /**
     * @var string CSS class name added to labels
     *
     * @see labelFor()
     */
    public $label_class = 'control-label';

    /**
     * @var string suffix to append to all labels (e.g. ":")
     *
     * @see labelFor()
     */
    public $label_suffix = '';

    /**
     * @var string CSS class-name added to required fields
     *
     * @see groupFor()
     */
    public $required_class = 'required';

    /**
     * @var string CSS class-name added to fields with error state
     *
     * @see groupFor()
     */
    public $error_class = 'has-error';

    /**
     * @var string group tag name (e.g. "div", "fieldset", etc.; defaults to "div")
     *
     * @see groupFor()
     * @see endGroup()
     */
    public $group_tag = 'div';

    /**
     * @var array default attributes to be added to opening control-group tags
     *
     * @see groupFor()
     */
    public $group_attrs = ['class' => 'form-group'];

    /**
     * @var string[] map where Field name => label override
     */
    protected $labels = [];

    /**
     * @var string[] map where Field name => placeholder override
     */
    protected $placeholders = [];

    /**
     * @var bool[] map where Field name => required flag
     */
    protected $required = [];

    /**
     * @var string[] list of void elements
     *
     * @see tag()
     *
     * @link http://www.w3.org/TR/html-markup/syntax.html#void-elements
     */
    private static $void_elements = [
        'area'    => true,
        'base'    => true,
        'br'      => true,
        'col'     => true,
        'command' => true,
        'embed'   => true,
        'hr'      => true,
        'img'     => true,
        'input'   => true,
        'keygen'  => true,
        'link'    => true,
        'meta'    => true,
        'param'   => true,
        'source'  => true,
        'track'   => true,
        'wbr'     => true,
    ];

    /**
     * @param InputModel|array|null $model           input model, or (possibly nested) input array (e.g. $_GET or $_POST)
     * @param string|string[]|null  $collection_name collection name(s) for inputs, e.g. 'myform' or ['myform', '123'] etc.
     * @param string|null           $id_prefix       base id for inputs, e.g. 'myform' or 'myform-123', etc.
     */
    public function __construct($model = null, $collection_name = null, $id_prefix = null)
    {
        $this->model = InputModel::create($model);
        $this->collection_name = $collection_name;
        $this->id_prefix = $id_prefix === null
            ? ($collection_name === null
                ? null
                : implode('-', (array) $this->collection_name))
            : $id_prefix;
    }

    /**
     * @param FieldInterface $field
     *
     * @return string
     *
     * @see Field::getLabel()
     */
    public function getLabel(FieldInterface $field)
    {
        return array_key_exists($field->getName(), $this->labels)
            ? $this->labels[$field->getName()]
            : $field->getLabel();
    }

    /**
     * Override the label defined by the Field
     *
     * @param FieldInterface $field
     * @param string         $label
     */
    public function setLabel(FieldInterface $field, $label)
    {
        $this->labels[$field->getName()] = $label;
    }

    /**
     * @param FieldInterface $field
     *
     * @return string
     *
     * @see Field::getPlaceholder()
     */
    public function getPlaceholder(FieldInterface $field)
    {
        return array_key_exists($field->getName(), $this->placeholders)
            ? $this->placeholders[$field->getName()]
            : $field->getPlaceholder();
    }

    /**
     * Override the placeholder label defined by the Field
     *
     * @param FieldInterface $field
     * @param string         $placeholder
     */
    public function setPlaceholder(FieldInterface $field, $placeholder)
    {
        $this->placeholders[$field->getName()] = $placeholder;
    }

    /**
     * @param FieldInterface $field
     *
     * @return string|null computed name-attribute
     */
    public function getName(FieldInterface $field)
    {
        $names = (array) $this->collection_name;
        $names[] = $field->getName();

        return $names[0] . (count($names) > 1 ? '[' . implode('][', array_slice($names, 1)) . ']' : '');
    }

    /**
     * @param FieldInterface $field
     *
     * @return string|null computed id-attribute
     */
    public function getId(FieldInterface $field)
    {
        return $this->id_prefix
            ? $this->id_prefix . '-' . $field->getName()
            : null;
    }

    /**
     * Conditionally create (or add) CSS class-names for Field status, e.g.
     * {@see $required_class} for {@see Field::$required} and {@see $error_class}
     * if the {@see $model} contains an error.
     *
     * @param FieldInterface $field
     *
     * @return array map of HTML attributes (with additonial classes)
     *
     * @see $required_class
     * @see $error_class
     */
    public function getAttrs(FieldInterface $field)
    {
        $classes = [];

        if ($this->required_class !== null && $this->isRequired($field)) {
            $classes[] = $this->required_class;
        }

        if ($this->error_class !== null && $this->model->hasError($field)) {
            $classes[] = $this->error_class;
        }

        return ['class' => $classes];
    }

    /**
     * @param FieldInterface $field
     *
     * @return bool true, if the given Field is required
     */
    public function isRequired(FieldInterface $field)
    {
        return array_key_exists($field->getName(), $this->required)
            ? $this->required[$field->getName()]
            : $field->isRequired();
    }

    /**
     * Override the required flag defined by the Field
     *
     * @param FieldInterface $field
     * @param bool           $required
     */
    public function setRequired(FieldInterface $field, $required = true)
    {
        $this->required[$field->getName()] = (bool) $required;
    }

    /**
     * Build an HTML input for a given Field.
     *
     * @param FieldInterface $field
     * @param array          $attr
     *
     * @return string
     *
     * @throws RuntimeException if the given Field cannot be rendered
     */
    public function render(FieldInterface $field, array $attr = [])
    {
        return $field->renderInput($this, $this->model, $attr);
    }

    /**
     * Builds an HTML group containing a label and rendered input for a given Field.
     *
     * @param FieldInterface $field
     * @param string|null    $label      label text (optional)
     * @param array          $input_attr map of HTML attributes for the input (optional)
     * @param array          $group_attr map of HTML attributes for the group (optional)
     *
     * @return string
     */
    public function renderGroup(FieldInterface $field, $label = null, array $input_attr = [], $group_attr = [])
    {
        return
            $this->groupFor($field, $group_attr)
            . $this->labelFor($field, $label)
            . $this->render($field, $input_attr)
            . $this->endGroup();
    }

    /**
     * Builds an HTML div with state-classes, containing a rendered input for a given Field.
     *
     * @param FieldInterface $field
     * @param array          $input_attr attributes for the generated input
     * @param array          $div_attr   attributes for the wrapper div
     *
     * @return string HTML
     */
    public function renderDiv(FieldInterface $field, array $input_attr = [], $div_attr = [])
    {
        return $this->divFor($field, $this->render($field, $input_attr), $div_attr);
    }

    /**
     * Visit a given Field - temporarily swaps out {@see $model}, {@see $name_prefix}
     * and {@see $id_prefix} and merges any changes made to the model while calling
     * the given function.
     *
     * @param FieldInterface|int|string $field Field instance, or an integer index, or string key
     * @param callable                  $func  function (InputModel $model): mixed
     *
     * @return mixed
     */
    public function visit($field, $func)
    {
        $model = $this->model;
        $name_prefix = $this->collection_name;
        $id_prefix = $this->id_prefix;

        $key = $field instanceof FieldInterface
            ? $field->getName()
            : (string) $field;

        $this->model = InputModel::create(@$model->input[$key], $model->getError($key));
        $this->collection_name = array_merge((array) $this->collection_name, [$key]);
        $this->id_prefix = $this->id_prefix
            ? $this->id_prefix . '-' . $key
            : null;

        call_user_func($func, $this->model);

        if ($this->model->input !== []) {
            $model->input[$key] = $this->model->input;
        } else {
            unset($model->input[$key]);
        }

        if ($this->model->hasErrors()) {
            $model->setError($key, $this->model->getErrors());
        }

        $this->model = $model;
        $this->collection_name = $name_prefix;
        $this->id_prefix = $id_prefix;
    }

    /**
     * Merge any number of attribute maps, with the latter overwriting the first, and
     * with special handling for the class-attribute.
     *
     * @param array ...$attr
     *
     * @return array
     */
    public function mergeAttrs()
    {
        $maps = func_get_args();

        $result = [];

        foreach ($maps as $map) {
            if (isset($map['class'])) {
                if (isset($result['class'])) {
                    $map['class'] = array_merge((array) $result['class'], (array) $map['class']);
                }
            }

            $result = array_merge($result, $map);
        }

        return $result;
    }

    /**
     * Encode plain text as HTML
     *
     * @param string $text  plain text
     * @param int    $flags encoding flags (optional, see htmlspecialchars)
     *
     * @return string escaped HTML
     *
     * @see softEscape()
     */
    public function escape($text, $flags = ENT_COMPAT)
    {
        return htmlspecialchars($text, $flags, $this->encoding, true);
    }

    /**
     * Encode plain text as HTML, while attempting to avoid double-encoding
     *
     * @param string $text  plain text
     * @param int    $flags encoding flags (optional, see htmlspecialchars)
     *
     * @return string escaped HTML
     *
     * @see escape()
     */
    public function softEscape($text, $flags = ENT_COMPAT)
    {
        return htmlspecialchars($text, $flags, $this->encoding, false);
    }

    /**
     * Build an opening and closing HTML tag (or a self-closing tag) - examples:
     *
     *     echo $renderer->tag('input', array('type' => 'text'));  => <input type="text"/>
     *
     *     echo $renderer->tag('div', array(), 'Foo &amp; Bar');   => <div>Foo &amp; Bar</div>
     *
     *     echo $renderer->tag('script', array(), '');             => <script></script>
     *
     * @param string      $name HTML tag name
     * @param array       $attr map of HTML attributes
     * @param string|null $html inner HTML, or NULL to build a self-closing tag
     *
     * @return string
     *
     * @see openTag()
     */
    public function tag($name, array $attr = [], $html = null)
    {
        return $html === null && isset(self::$void_elements[$name])
            ? '<' . $name . $this->attrs($attr) . '/>'
            : '<' . $name . $this->attrs($attr) . '>' . $html . '</' . $name . '>';
    }

    /**
     * Build an open HTML tag; remember to close the tag.
     *
     * Note that there is no closeTag() equivalent, as this wouldn't help with anything
     * and would actually require more code than e.g. a simple literal `</div>`
     *
     * @param string $name HTML tag name
     * @param array  $attr map of HTML attributes
     *
     * @return string
     *
     * @see tag()
     */
    public function openTag($name, array $attr = [])
    {
        return '<' . $name . $this->attrs($attr) . '>';
    }

    /**
     * Build HTML attributes for use inside an HTML (or XML) tag.
     *
     * Includes a leading space, since this is usually used inside a tag, e.g.:
     *
     *     <div<?= $form->attrs(array('class' => 'foo')) ?>>...</div>
     *
     * Accepts strings, or arrays of strings, as attribute-values - arrays will
     * be folded using space as a separator, e.g. useful for the class-attribute.
     *
     * Attributes containing NULL, FALSE or an empty array() are ignored.
     *
     * Attributes containing TRUE are rendered as value-less attributes.
     *
     * @param array $attr map where attribute-name => attribute value(s)
     * @param bool  $sort true, to sort attributes by name; otherwise false (sorting is enabled by default)
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
            if (is_array($value)) {
                $value = count($value)
                    ? implode(' ', $value) // fold multi-value attribute (e.g. class-names)
                    : null; // filter empty array
            }

            if ($value === null || $value === false) {
                continue; // skip NULL and FALSE attributes
            }

            if ($value === true) {
                $html .= $this->xhtml ?
                    ' ' . $name . '="' . $name . '"' // e.g. disabled="disabled" (as required for XHTML)
                    : ' ' . $name; // value-less HTML attribute
            } else {
                $html .= ' ' . $name . '="' . $this->escape($value) . '"';
            }
        }

        return $html;
    }

    /**
     * Builds an HTML <input> tag
     *
     * @param string $type
     * @param string $name
     * @param string $value
     * @param array  $attr map of HTML attributes
     *
     * @return string
     *
     * @see inputFor()
     */
    public function input($type, $name = null, $value= null, $attr = [])
    {
        return $this->tag(
            'input',
            $this->mergeAttrs(
                [
                    'type'  => $type,
                    'name'  => $name,
                    'value' => $value,
                ],
                $attr
            )
        );
    }

    /**
     * Build an HTML input-tag for a given Field
     *
     * @param FieldInterface $field
     * @param string         $type HTML input type-attribute (e.g. "text", "password", etc.)
     * @param array          $attr map of HTML attributes
     *
     * @return string
     */
    public function inputFor(FieldInterface $field, $type, array $attr = [])
    {
        return $this->tag(
            'input',
            $this->mergeAttrs(
                [
                    'name'        => $this->getName($field),
                    'id'          => $this->getId($field),
                    'class'       => $this->input_class,
                    'value'       => $this->model->getInput($field),
                    'type'        => $type,
                    'placeholder' => @$attr['placeholder'] ?: $this->getPlaceholder($field),
                ],
                $attr
            )
        );
    }

    /**
     * Build an HTML opening tag for an input group
     *
     * Call {@see endGroup()} to create the matching closing tag.
     *
     * @param array $attr optional map of HTML attributes
     *
     * @return string
     *
     * @see groupFor()
     */
    public function group($attr = [])
    {
        return $this->openTag(
            $this->group_tag,
            $this->mergeAttrs($this->group_attrs, $attr)
        );
    }

    /**
     * Build an HTML opening tag for an input group, with CSS classes added for
     * {@see Field::$required} and error state, as needed.
     *
     * Call {@see endGroup()} to create the matching closing tag.
     *
     * @param FieldInterface $field
     * @param array          $attr map of HTML attributes (optional)
     *
     * @return string
     *
     * @see $group_tag
     * @see $group_attrs
     * @see $required_class
     * @see $error_class
     * @see endGroup()
     */
    public function groupFor(FieldInterface $field, array $attr = [])
    {
        return $this->openTag(
            $this->group_tag,
            $this->mergeAttrs($this->group_attrs, $this->getAttrs($field), $attr)
        );
    }

    /**
     * Returns the matching closing tag for a {@see group()} or {@see groupFor()} tag.
     *
     * @return string
     *
     * @see groupFor()
     * @see $group_tag
     */
    public function endGroup()
    {
        return "</{$this->group_tag}>";
    }

    /**
     * Builds an HTML div with state-classes, containing the given HTML.
     *
     * @param FieldInterface $field
     * @param string         $html inner HTML for the generated div
     * @param array          $attr additional attributes for the div
     *
     * @return string HTML
     */
    public function divFor(FieldInterface $field, $html, array $attr = [])
    {
        return $this->tag('div', $this->mergeAttrs($this->getAttrs($field), $attr), $html);
    }

    /**
     * Build a `<label for="id" />` tag
     *
     * @param string $for   target element ID
     * @param string $label label text
     * @param array  $attr  map of HTML attributes
     *
     * @return string
     *
     * @see labelFor()
     */
    public function label($for, $label, $attr = [])
    {
        return $this->tag(
            'label',
            $this->mergeAttrs(
                [
                    'for' => $for,
                    'class' => $this->label_class
                ],
                $attr
            ),
            $this->softEscape($label . $this->label_suffix)
        );
    }

    /**
     * Build an HTML `<label for="id" />` tag
     *
     * @param FieldInterface $field
     * @param string|null    $label label text (optional)
     * @param array          $attr  map of HTML attributes
     *
     * @return string
     *
     * @see Field::getLabel()
     *
     * @throws RuntimeException if a label cannot be produced
     */
    public function labelFor(FieldInterface $field, $label = null, array $attr = [])
    {
        $id = $this->getId($field);

        if ($id === null) {
            throw new RuntimeException("cannot produce a label when FormHelper::\$id_prefix is NULL");
        }

        if ($label === null) {
            $label = $this->getLabel($field);

            if ($label === null) {
                throw new RuntimeException("the given Field has no defined label");
            }
        }

        return $this->label($id, $label);
    }
}
