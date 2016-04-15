<?php

namespace mindplay\kissform\Validators;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\Facets\ValidatorInterface;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;

/**
 * Validate input matching a regular expression pattern.
 */
class CheckPattern implements ValidatorInterface
{
    /**
     * @var string
     */
    private $pattern;

    /**
     * @var string
     */
    private $error;

    /**
     * @param string $pattern regular expression pattern to match
     * @param string $error   error message
     */
    public function __construct($pattern, $error)
    {
        $this->pattern = $pattern;
        $this->error = $error;
    }

    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation)
    {
        $input = $model->getInput($field);

        if ($input === null) {
            return; // no value
        }

        if (!preg_match($this->pattern, $input)) {
            $model->setError($field, $this->error);
        }
    }
}
