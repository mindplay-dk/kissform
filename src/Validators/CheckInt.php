<?php

namespace mindplay\kissform\Validators;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\Facets\ValidatorInterface;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;
use mindplay\lang;

/**
 * Validate whole number input.
 */
class CheckInt implements ValidatorInterface
{
    /**
     * @var string|null
     */
    private $error;

    /**
     * @param string|null $error optional custom error message
     */
    public function __construct($error = null)
    {
        $this->error = $error;
    }

    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation)
    {
        $input = $model->getInput($field);

        if ($input === null) {
            return; // no input
        }

        if (filter_var($input, FILTER_VALIDATE_INT) === false) {
            $model->setError($field, $this->error ?: lang::text("mindplay/kissform", "int", ["field" => $validation->getTitle($field)]));
        }
    }
}
