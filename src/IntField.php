<?php

namespace mindplay\kissform;

use InvalidArgumentException;

/**
 * This class provides information about an integer field.
 */
class IntField extends TextField
{
    /** @var int|null minimum value */
    public $min_value;

    /** @var int|null maximum value */
    public $max_value;

    /**
     * @param InputModel $model
     *
     * @return int|null
     */
    public function getValue(InputModel $model)
    {
        $input = $model->getInput($this);

        if ($input === null) {
            return null; // no input available
        }

        if (is_int($input) || is_numeric($input)) {
            return (int) $input;
        }

        throw new InvalidArgumentException("unexpected input value: {$input}");
    }

    /**
     * @param InputModel $model
     * @param int|null   $value
     *
     * @return void
     */
    public function setValue(InputModel $model, $value)
    {
        if (is_int($value)) {
            $model->setInput($this, (string)$value);
        } elseif ($value === null) {
            $model->setInput($this, null);
        } else {
            throw new InvalidArgumentException("unexpected value: {$value}");
        }
    }
}
