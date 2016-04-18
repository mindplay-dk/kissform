<?php

namespace mindplay\kissform\Validators;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\Facets\ValidatorInterface;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;
use mindplay\lang;

/**
 * Validate required input
 */
class CheckRequired implements ValidatorInterface
{
    /**
     * @var string|null
     */
    private $error;

    /**
     * @param string $error optional custom error message
     */
    public function __construct($error = null)
    {
        $this->error = $error;
    }

    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation)
    {
        if ($model->getInput($field) === null) {
            $model->setError(
                $field,
                $this->error ?: lang::text("mindplay/kissform", "checked", ["field" => $validation->getLabel($field)])
            );
        }
    }
}
