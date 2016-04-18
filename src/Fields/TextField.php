<?php

namespace mindplay\kissform\Fields;

use mindplay\kissform\Field;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\Validators\CheckLength;
use mindplay\kissform\Validators\CheckMaxLength;
use mindplay\kissform\Validators\CheckMinLength;
use mindplay\kissform\Validators\CheckPattern;

/**
 * This class provides information about a text field, e.g. a plain
 * input type=text element.
 */
class TextField extends Field
{
    /**
     * @var int|null
     */
    public $min_length;

    /**
     * @var int|null
     */
    public $max_length;

    /**
     * @var string|null
     */
    protected $pattern;

    /**
     * @var string|null
     */
    protected $pattern_error;

    /**
     * @param string $pattern regular expression pattern (optional; no delimiters, modifiers or anchors)
     * @param string $error   error message to apply on pattern mismatch
     */
    public function setPattern($pattern, $error)
    {
        $this->pattern = $pattern;
        $this->pattern_error = $error;
    }

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        $defaults = [];

        if ($this->max_length) {
            $defaults['maxlength'] = $this->max_length;
        }

        if ($this->pattern) {
            $defaults['pattern'] = $this->pattern;
            $defaults['data-pattern-error'] = $this->pattern_error;
        }
        
        return $renderer->inputFor($this, 'text', $attr + $defaults);
    }

    /**
     * @inheritdoc
     */
    public function createValidators()
    {
        $validators = parent::createValidators();

        if ($this->min_length !== null) {
            if ($this->max_length !== null) {
                $validators[] = new CheckLength($this->min_length, $this->max_length);
            } else {
                $validators[] = new CheckMinLength($this->min_length);
            }
        } else if ($this->max_length !== null) {
            $validators[] = new CheckMaxLength($this->max_length);
        }

        if ($this->pattern) {
            $validators[] = new CheckPattern("/^{$this->pattern}$/", $this->pattern_error);
        }

        return $validators;
    }
}
