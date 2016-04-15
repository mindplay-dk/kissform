<?php

namespace mindplay\kissform\Fields;

use mindplay\kissform\Field;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\Validators\CheckLength;
use mindplay\kissform\Validators\CheckMaxLength;
use mindplay\kissform\Validators\CheckMinLength;

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
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        return $renderer->inputFor($this, 'text', array_merge(['maxlength' => $this->max_length], $attr));
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

        return $validators;
    }
}
