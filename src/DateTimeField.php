<?php

namespace mindplay\kissform;

use DateTimeZone;
use InvalidArgumentException;

/**
 * This class provides information about a date/time input.
 *
 * You should specify the {@link $format} and call {@link setTimeZone()} - by default,
 * the timezone is obtained from {@date_default_timezone_get()} which is system-dependent.
 *
 * Format string is similar that of {@link date()} - see documentation below:
 *
 * {@link http://php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters}
 */
class DateTimeField extends Field implements RenderableField
{
    /**
     * @var string input date/time format string
     */
    public $format = 'Y-m-d H:i:s';

    /**
     * @var DateTimeZone input time-zone
     */
    public $timezone;

    /**
     * @var string[] map of HTML attributes to apply
     */
    public $attrs = array(
        'readonly' => 'readonly',
        'data-ui'  => 'datetimepicker',
    );

    /**
     * @param string $name field name
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->setTimeZone(null);
    }

    /**
     * @param DateTimeZone|string|null $timezone input time-zone (or NULL to use the current default timezone)
     */
    public function setTimeZone($timezone)
    {
        if ($timezone === null) {
            $timezone = new DateTimeZone(date_default_timezone_get());
        } else if (is_string($timezone)) {
            $timezone = new DateTimeZone($timezone);
        } else if (! $timezone instanceof DateTimeZone) {
            throw new InvalidArgumentException('DateTimeZone or string expected, ' . gettype($timezone) . ' given');
        }

        $this->timezone = $timezone;
    }

    // TODO getInput() and setInput() and unit-test

    // TODO extract abstract base-class and add DateField

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        return $renderer->buildInput($this, 'text', $attr + $this->attrs);
    }
}
