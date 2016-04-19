<?php

namespace mindplay\kissform\Fields;

use InvalidArgumentException;
use mindplay\kissform\Fields\Base\NumericField;
use mindplay\kissform\InputModel;
use mindplay\kissform\Validators\CheckInt;
use UnexpectedValueException;

/**
 * This class provides information about an integer field.
 * 
 * TODO extract into NumericField base class with $allow_float property, refactor validation, adjust HTML5 attributes
 */
class IntField extends NumericField
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
    protected function createTypeValidator()
    {
        return new CheckInt();
    }
}
