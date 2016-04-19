<?php

namespace mindplay\kissform\Fields;

use InvalidArgumentException;
use mindplay\kissform\Fields\Base\NumericField;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\Validators\CheckFloat;
use mindplay\kissform\Validators\CheckInt;
use UnexpectedValueException;

/**
 * This class provides information about a floating point field.
 */
class FloatField extends NumericField
{
    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        $pattern = $this->min_value === null || $this->min_value < 0
            ? '-?\d*(\.(?=\d))?\d*' // accept negative
            : '\d*(\.(?=\d))?\d*';
        
        return parent::renderInput($renderer, $model, $attr + ['pattern' => $pattern]);
    }

    /**
     * @param InputModel $model
     *
     * @return float|null
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
            return (float) $input;
        }

        throw new UnexpectedValueException("unexpected input: {$input}");
    }

    /**
     * @param InputModel $model
     * @param float|null $value
     *
     * @return void
     *
     * @throws InvalidArgumentException if the given value is unacceptable.
     */
    public function setValue(InputModel $model, $value)
    {
        if (is_numeric($value)) {
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
    protected function createTypeValidator()
    {
        return new CheckFloat();
    }
}
