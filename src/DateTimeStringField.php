<?php

namespace mindplay\kissform;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use UnexpectedValueException;

/**
 * Base date/time field-type for string-based input.
 */
class DateTimeStringField extends TimeZoneAwareField implements RenderableField
{
    /**
     * @var string input date/time format string
     */
    public $format;

    /**
     * @var string[] map of HTML attributes to apply
     */
    public $attrs = array('readonly' => true);

    /**
     * Attempts to parse the given input; returns NULL on failure.
     *
     * @param string $input
     *
     * @return int|null
     */
    public function parseInput($input)
    {
        $time = @date_create_from_format($this->format, $input, $this->timezone);

        return $time && ($time->format($this->format) == $input)
            ? $time->getTimestamp()
            : null;
    }

    /**
     * @param InputModel $model
     *
     * @return int|null timestamp
     *
     * @throws UnexpectedValueException if unable to parse the input
     */
    public function getValue(InputModel $model)
    {
        $input = $model->getInput($this);

        if (empty($input)) {
            return null;
        } else {
            $value = $this->parseInput($input);

            if ($value === null) {
                throw new UnexpectedValueException("invalid input");
            }

            return $value;
        }
    }

    /**
     * @param InputModel $model
     * @param int|null   $value timestamp
     *
     * @return void
     *
     * @throws InvalidArgumentException if the given value is unacceptable.
     */
    public function setValue(InputModel $model, $value)
    {
        if ($value === null) {
            $model->setInput($this, null);
        } elseif (is_int($value)) {
            $date = new DateTime();
            $date->setTimestamp($value);
            $date->setTimezone($this->timezone);

            $model->setInput($this, $date->format($this->format));
        } else {
            throw new InvalidArgumentException("integer timestamp expected");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        return $renderer->buildInput($this, 'text', $attr + $this->attrs);
    }
}
