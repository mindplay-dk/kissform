<?php

namespace mindplay\kissform\Validators;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\Facets\ValidatorInterface;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;
use mindplay\lang;

/**
 * Validate an acceptance checkbox (for confirmations, e.g. accepted privacy policy or terms of service)
 */
class CheckAccept implements ValidatorInterface
{
    /**
     * @var string expected checkbox value
     */
    private $checked_value;

    /**
     * @var string|null
     */
    private $error;

    /**
     * @param string      $checked_value expected checkbox value
     * @param string|null $error         optional custom error message
     */
    public function __construct($checked_value, $error = null)
    {
        $this->checked_value = $checked_value;
        $this->error = $error;
    }

    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation)
    {
        $value = $model->getInput($field);

        if ($value != $this->checked_value) {
            $model->setError(
                $field,
                $this->error ?: lang::text("mindplay/kissform", "checked", ["field" => $validation->getTitle($field)])
            );
        }
    }
}
