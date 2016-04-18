<?php

namespace mindplay\kissform\Validators;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\Facets\ValidatorInterface;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;
use mindplay\lang;

/**
 * Validate min/max length of a string.
 */
class CheckLength implements ValidatorInterface
{
    /**
     * @var string|null
     */
    private $error;

    /**
     * @var int
     */
    private $min;

    /**
     * @var int
     */
    private $max;

    /**
     * @param int         $min   min. length
     * @param int         $max   max. length
     * @param string|null $error optional custom error message
     */
    public function __construct($min, $max, $error = null)
    {
        $this->error = $error;
        $this->min = $min;
        $this->max = $max;
    }

    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation)
    {
        $input = $model->getInput($field);

        if ($input === null) {
            return; // no input given
        }

        $length = mb_strlen($input);

        if ($length < $this->min || $length > $this->max) {
            $model->setError(
                $field,
                $this->error ?: lang::text("mindplay/kissform", "length", ["field" => $validation->getLabel($field), "min" => $this->min, "max" => $this->max])
            );
        }
    }
}
