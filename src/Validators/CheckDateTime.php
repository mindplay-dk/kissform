<?php

namespace mindplay\kissform\Validators;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\Facets\ParserInterface;
use mindplay\kissform\Facets\ValidatorInterface;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;
use mindplay\lang;

/**
 * Validate date or date/time input in the format specified by the given {@see DateTimeStringField}.
 */
class CheckDateTime implements ValidatorInterface
{
    /**
     * @var ParserInterface
     */
    private $parser;

    /**
     * @var string|null
     */
    private $error;

    /**
     * @param ParserInterface $parser the input parser required to parse the input
     * @param string|null     $error  optional custom error message
     */
    public function __construct(ParserInterface $parser, $error = null)
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
            $model->setError(
                $field,
                $this->error ?: lang::text("mindplay/kissform", "datetime", ["field" => $validation->getTitle($field)])
            );
        }
    }
}
