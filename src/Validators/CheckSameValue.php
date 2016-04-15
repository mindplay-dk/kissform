<?php

namespace mindplay\kissform\Validators;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\Facets\ValidatorInterface;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;
use mindplay\lang;

/**
 * Validate repeated input (e.g. confirming e-mail address or password, etc.)
 *
 * This validation should be applied to a secondary input confirmation field - it's value
 * will be compared against the value of a specified primary input field.
 *
 * In a scenario where the user is required to confirm e.g. an e-mail address or password,
 * apply the e-mail or password validation to the primary field - then apply this validation
 * to the secondary field.
 */
class CheckSameValue implements ValidatorInterface
{
    /**
     * @var FieldInterface
     */
    private $primary_field;

    /**
     * @var string|null
     */
    private $error;

    /**
     * @param FieldInterface $primary_field primary input field
     * @param string|null    $error         optional custom error message
     */
    public function __construct(FieldInterface $primary_field, $error = null)
    {
        $this->primary_field = $primary_field;
        $this->error = $error;
    }

    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation)
    {
        if ($model->getInput($field) !== $model->getInput($this->primary_field)) {
            $model->setError(
                $field,
                $this->error ?: lang::text("mindplay/kissform", "confirm", ["field" => $validation->getTitle($field)])
            );
        }
    }
}
