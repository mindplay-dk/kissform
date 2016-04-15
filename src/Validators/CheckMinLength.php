<?php

namespace mindplay\kissform\Validators;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\Facets\ValidatorInterface;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;
use mindplay\lang;

/**
 * Validate minimum length of a string.
 */
class CheckMinLength implements ValidatorInterface
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
     * @param int|null    $min   min. length
     * @param string|null $error optional custom error message
     */
    public function __construct($min, $error = null)
    {
        $this->error = $error;
        $this->min = $min;
    }

    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation)
    {
        $input = $model->getInput($field);

        if ($input === null) {
            return; // no input
        }

        $length = mb_strlen($input);

        if ($length < $this->min) {
            $model->setError(
                $field,
                $this->error ?: lang::text("mindplay/kissform", "minLength", ["field" => $validation->getTitle($field), "min" => $this->min])
            );
        }
    }
}
