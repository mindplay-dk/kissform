<?php

namespace mindplay\kissform\Validators;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\Facets\ValidatorInterface;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;
use mindplay\lang;

/**
 * Validate maximum length of a string.
 */
class CheckMaxLength implements ValidatorInterface
{
    /**
     * @var string|null
     */
    private $error;

    /**
     * @var int
     */
    private $max;

    /**
     * @param int         $max   max. length
     * @param string|null $error optional custom error message
     */
    public function __construct($max, $error = null)
    {
        $this->error = $error;
        $this->max = $max;
    }

    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation)
    {
        $input = $model->getInput($field);

        if ($input === null) {
            return; // no input
        }

        $length = mb_strlen($input);

        if ($length > $this->max) {
            $model->setError(
                $field,
                $this->error ?: lang::text("mindplay/kissform", "maxLength", ["field" => $validation->getLabel($field), "max" => $this->max])
            );
        }
    }
}
