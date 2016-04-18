<?php

namespace mindplay\kissform\Validators;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;
use mindplay\lang;

/**
 * Validate numerical value within a min/max range.
 */
class CheckRange extends CheckNumeric
{
    /**
     * @var int|float
     */
    private $min;

    /**
     * @var int|float
     */
    private $max;

    /**
     * @param int|float   $min   min value
     * @param int|float   $max   max value
     * @param string|null $error optional custom error message
     */
    public function __construct($min, $max, $error = null)
    {
        parent::__construct($error);

        $this->min = $min;
        $this->max = $max;
    }

    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation)
    {
        parent::validate($field, $model, $validation);

        if ($model->hasError($field)) {
            return; // parent validation (IsNumber) failed
        }

        $input = $model->getInput($field);

        if ($input === null) {
            return; // no input, no minimum value
        }

        if ($input < $this->min || $input > $this->max) {
            $model->setError(
                $field,
                $this->error ?: lang::text("mindplay/kissform", "range", ["field" => $validation->getLabel($field), "min" => $this->min, "max" => $this->max])
            );
        }
    }
}
