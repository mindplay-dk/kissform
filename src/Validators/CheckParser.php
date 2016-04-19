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
    private $error_id;

    /**
     * @param ParserInterface $parser   the InputParser (usually a Field) which should attempt to parse the input
     * @param string          $error_id error message translation key
     */
    public function __construct(ParserInterface $parser, $error_id)
    {
        $this->parser = $parser;
        $this->error_id = $error_id;
    }

    public function validate(FieldInterface $field, InputModel $model, InputValidation $validation)
    {
        $input = $model->getInput($field);

        if ($input === null) {
            return; // no input
        }

        if ($this->parser->parseInput($input) === null) {
            $model->setError($field, lang::text("mindplay/kissform", $this->error_id, ["field" => $validation->getLabel($field)]));
        }
    }
}
