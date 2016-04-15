<?php

namespace mindplay\kissform\Validators;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;
use mindplay\lang;

/**
 * Validate numerical value with a minimum.
 */
class CheckMinValue extends CheckNumeric
{
    /**
     * @var int|float
     */
    private $min;

    /**
     * @param int|float   $min   min value
     * @param string|null $error optional custom error message
     */
    public function __construct($min, $error = null)
    {
        parent::__construct($error);

        $this->min = $min;
    }

    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation)
    {
        parent::validate($field, $model, $validation);

        if ($model->hasError($field)) {
            return; // parent validation (IsNumber) failed
        }

        $input = $model->getInput($field);

        if ($input === null) {
            return; // no value given
        }

        if ($input < $this->min) {
            $model->setError(
                $field,
                $this->error ?: lang::text("mindplay/kissform", "minValue", ["field" => $validation->getTitle($field), "min" => $this->min])
            );
        }
    }
}
