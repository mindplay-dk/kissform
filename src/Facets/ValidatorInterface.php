<?php

namespace mindplay\kissform\Facets;

use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;

/**
 * This interface outlines the responsibilities of a Validator component.
 */
interface ValidatorInterface
{
    /**
     * Validates the state of a given InputModel for a given Field, and updates the state
     * of the given InputValidation, e.g. adding errors, if applicable.
     *
     * @param FieldInterface  $field
     * @param InputModel      $model
     * @param InputValidation $validation
     *
     * @return void
     */
    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation);
}
