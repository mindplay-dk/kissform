<?php

namespace mindplay\kissform;

use InvalidArgumentException;
use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\Validators\CheckRequired;

/**
 * Optional base class for mutable input field metadata types.
 *
 * The getValue() and setValue() methods may be overriden in field types
 * and should perform conversion to/from native values, and should throw
 * exceptions if invalid input or values is given, because validation is
 * assumed to have taken place in advance. The default implementations
 * handle strings and can be inherited in string-type fields.
 *
 * getValue() overrides should throw an {@see UnexpectedValueException}
 * if the state of the input model is invalid.
 *
 * setValue() overrides should throw an {@see InvalidArgumentException}
 * if the given value is unacceptable.
 */
abstract class Field implements FieldInterface
{
    /**
     * @var string field name
     */
    protected $name;

    /**
     * @var string field label (for labels associated with this input field on a form)
     */
    protected $label;

    /**
     * @var string field input placeholder value (for input placeholders on forms)
     */
    protected $placeholder;

    /**
     * @var bool true, if this field is required
     */
    protected $required = false;

    /**
     * @param string $name field name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function getValue(InputModel $model)
    {
        return $model->getInput($this);
    }

    public function createValidators()
    {
        return $this->isRequired()
            ? [new CheckRequired()]
            : [];
    }

    /**
     * @param string $label display label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @param string $placeholder placeholder label
     */
    public function setPlaceholder($placeholder)
    {
        $this->placeholder = $placeholder;
    }

    /**
     * @param bool $required TRUE, if input is required for this Field; FALSE, if it's optional
     */
    public function setRequired($required = true)
    {
        $this->required = (bool) $required;
    }

    /**
     * @param InputModel  $model
     * @param string|null $value
     *
     * @return void
     *
     * @throws InvalidArgumentException if the given value is unacceptable.
     */
    public function setValue(InputModel $model, $value)
    {
        if (is_scalar($value)) {
            $model->setInput($this, (string) $value);
        } elseif ($value === null) {
            $model->setInput($this, null);
        } else {
            throw new InvalidArgumentException("string expected");
        }
    }
}
