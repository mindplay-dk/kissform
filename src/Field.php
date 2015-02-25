<?php

namespace mindplay\kissform;

/**
 * Abstract base class for input field metadata types.
 *
 * The getValue() and setValue() methods should be overriden in field types
 * and should perform conversion to/from native values, and should throw
 * exceptions if invalid input or values is given, because validation is
 * assumed to have taken place in advance. The default implementations
 * handle strings and can be inherited in string-type fields.
 */
abstract class Field
{
    /** @var string field name */
    public $name;

    /** @var string field label (for labels associated with this input field on a form) */
    public $label;

    /** @var string field input placeholder value (for input placeholders on forms) */
    public $placeholder;

    /** @var bool true, if this field is required */
    public $required = false;

    /**
     * @param string $name field name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @param InputModel $model
     *
     * @return string|null
     */
    public function getValue(InputModel $model)
    {
        return $model->getInput($this);
    }

    /**
     * @param InputModel  $model
     * @param string|null $value
     *
     * @return void
     */
    public function setValue(InputModel $model, $value)
    {
        $model->setInput($this, $value);
    }
}
