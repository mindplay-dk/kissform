<?php

namespace mindplay\kissform\Validators;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;
use mindplay\lang;

/**
 * Validate numerical value with a maximum.
 */
class CheckMaxValue extends CheckFloat
{
    /**
     * @var int|float|null
     */
    private $max;

    /**
     * @param int|float   $max   max value
     * @param string|null $error optional custom error message
     */
    public function __construct($max, $error = null)
    {
        parent::__construct($error);

        $this->max = $max;
    }

    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation)
    {
        parent::validate($field, $model, $validation);

        if ($model->hasError($field)) {
            return; // parent validation (IsNumber) failed
        }

        $input = $model->getInput($field);

        if ($input > $this->max) {
            $model->setError(
                $field,
                $this->error ?: lang::text("mindplay/kissform", "maxValue", ["field" => $validation->getLabel($field), "max" => $this->max])
            );
        }
    }
}
