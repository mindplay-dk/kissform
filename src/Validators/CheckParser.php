<?php

namespace mindplay\kissform\Validators;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\Facets\ParserInterface;
use mindplay\kissform\Facets\ValidatorInterface;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;
use mindplay\lang;

/**
 * Validate a Field by attempting to parse the input value.
 *
 * This is useful for e.g. date or date/time input which may be parsed by {@see DateTimeStringField}.
 */
class CheckParser implements ValidatorInterface
{
    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * @var string
     */
    private $error;

    /**
     * @param ParserInterface $parser the InputParser (usually a Field) which should attempt to parse the input
     * @param string          $error  error message template (the "{field}" token will be substituted)
     */
    public function __construct(ParserInterface $parser, $error)
    {
        $this->parser = $parser;
        $this->error = $error;
    }

    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation)
    {
        $input = $model->getInput($field);

        if ($input === null) {
            return; // no input
        }

        if ($this->parser->parseInput($input) === null) {
            $model->setError($field, strtr($this->error, ["field" => $validation->getLabel($field)]));
        }
    }
}
