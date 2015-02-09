<?php

namespace mindplay\kissform;

/**
 * Abstract base class for input field metadata types.
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
}
