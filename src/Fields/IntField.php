<?php

namespace mindplay\kissform\Fields;

use InvalidArgumentException;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\Validators\CheckInt;
use mindplay\kissform\Validators\CheckMaxValue;
use mindplay\kissform\Validators\CheckMinValue;
use mindplay\kissform\Validators\CheckRange;
use UnexpectedValueException;

/**
 * This class provides information about an integer field.
 */
class IntField extends TextField
{
    /**
     * @var int|null minimum value
     */
    public $min_value;

    /**
     * var int|null maximum value
     */
    public $max_value;

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        $defaults = [];

        if ($this->max_length) {
            $defaults['maxlength'] = $this->max_length;
        }

        if ($this->min_value) {
            $defaults['min'] = $this->min_value;
        }

        if ($this->max_value) {
            $defaults['max'] = $this->max_value;
        }

        return $renderer->inputFor($this, 'number', $attr + $defaults);
    }
    
    /**
     * @param InputModel $model
     *
     * @return int|null
     *
     * @throws UnexpectedValueException if unable to parse the input
     */
    public function getValue(InputModel $model)
    {
        $input = $model->getInput($this);

        if ($input === null) {
            return null; // no input available
        }

        if (is_numeric($input)) {
            return (int) $input;
        }

        throw new UnexpectedValueException("unexpected input: {$input}");
    }

    /**
     * @param InputModel $model
     * @param int|null   $value
     *
     * @return void
     *
     * @throws InvalidArgumentException if the given value is unacceptable.
     */
    public function setValue(InputModel $model, $value)
    {
        if (is_int($value)) {
            $model->setInput($this, (string) $value);
        } elseif ($value === null) {
            $model->setInput($this, null);
        } else {
            throw new InvalidArgumentException("unexpected value type: " . gettype($value));
        }
    }

    /**
     * @inheritdoc
     */
    public function createValidators()
    {
        $validators = parent::createValidators();

        if ($this->min_value !== null) {
            if ($this->max_value !== null) {
                $validators[] = new CheckRange($this->min_value, $this->max_value);
            } else {
                $validators[] = new CheckMinValue($this->min_value);
            }
        } else if ($this->max_value !== null) {
            $validators[] = new CheckMaxValue($this->max_value);
        } else {
            $validators[] = new CheckInt();
        }

        return $validators;
    }
}
