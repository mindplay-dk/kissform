<?php

namespace mindplay\kissform\Fields;

use DateTime;
use InvalidArgumentException;
use mindplay\kissform\Facets\ParserInterface;
use mindplay\kissform\Fields\Base\TimeZoneAwareField;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\Validators\CheckParser;
use mindplay\lang;
use UnexpectedValueException;

/**
 * Date/time field-type for string-based input.
 */
class DateTimeField extends TimeZoneAwareField implements ParserInterface
{
    /**
     * @var string input date/time format string
     */
    public $format;

    /**
     * @var string[] map of HTML attributes to apply
     */
    public $attrs;

    /**
     * @param string $name     field name
     * @param string $timezone timezone name
     * @param string $format   date/time format compatible with the date() function
     * @param array  $attrs    map of HTML attribtues to apply
     *
     * @see date()
     */
    public function __construct($name, $timezone, $format, $attrs = [])
    {
        parent::__construct($name);

        $this->setTimeZone($timezone);

        $this->format = $format;
        $this->attrs = $attrs;
    }

    /**
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
        return $renderer->inputFor($this, 'text', $attr + $this->attrs);
    }

    /**
     * {@inheritdoc}
     */
    public function createValidators()
    {
        $validators = parent::createValidators();

        $validators[] = new CheckParser($this, lang::text("mindplay/kissform", "datetime"));
        
        return $validators;
    }
}
