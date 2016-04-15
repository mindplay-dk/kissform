<?php

namespace mindplay\kissform\Validators;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\Facets\ValidatorInterface;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;
use mindplay\lang;

/**
 * Validate selection from a list of allowed values (for drop-down inputs, radio lists, etc.)
 *
 * This will automatically respect {@link Field::$required} so there is no need to manually
 * validate as required() beforehand.
 */
class CheckSelected implements ValidatorInterface
{
    /**
     * @var string[]|null
     */
    private $options;

    /**
     * @var string|null
     */
    private $error;

    /**
     * @param string[]|null $options list of allowed values
     * @param string|null   $error   optional custom error message
     */
    public function __construct(array $options, $error = null)
    {
        $this->options = $options;
        $this->error = $error;
    }

    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation)
    {
        $input = $model->getInput($field);

        if (($field->isRequired() === false) && ($input === null)) {
            return; // no input, not required
        }

        if (! in_array($input, $this->options)) {
            $model->setError(
                $field,
                $this->error ?: lang::text("mindplay/kissform", "selected", ["field" => $validation->getTitle($field)])
            );
        }
    }
}
