<?php

namespace mindplay\kissform;

use DateTime;
use DateTimeZone;
use IntlDateFormatter;
use UnexpectedValueException;
use InvalidArgumentException;

/**
 * This field type implements a date-picker using three (year/month/day) drop-downs.
 */
class DateSelectField extends TimeZoneAwareField implements RenderableField
{
    const KEY_YEAR = 'year';
    const KEY_MONTH = 'month';
    const KEY_DAY = 'day';

    /**
     * @var int first year available for selection in year drop-down
     */
    public $year_min;

    /**
     * @var int last year available for selection in year drop-down
     */
    public $year_max;

    /**
     * @var string[] list of month names
     */
    public $months = array(
        'January',
        'February',
        'March',
        'April',
        'May',
        'June',
        'July',
        'August',
        'September',
        'October',
        'November',
        'December',
    );

    /**
     * @var string CSS class-name for the year drop-down
     */
    public $year_class = self::KEY_YEAR;

    /**
     * @var string CSS class-name for the year drop-down
     */
    public $month_class = self::KEY_MONTH;

    /**
     * @var string CSS class-name for the year drop-down
     */
    public $day_class = self::KEY_DAY;

    /**
     * @param string $name field name
     * @param DateTimeZone|string|null $timezone input time-zone (or NULL to use the current default timezone)
     */
    public function __construct($name, $timezone = null)
    {
        parent::__construct($name);

        $this->setTimeZone($timezone);

        $this->setYearRange(0, -100);
    }

    /**
     * Set the range of years available in the year drop-down.
     *
     * Values are relative to current year - e.g. 0 is the current year, -1 is last
     * year, 10 is 10 years from now, etc.
     *
     * @param int $min
     * @param int $max
     *
     * @return void
     */
    public function setYearRange($min, $max)
    {
        $date = new DateTime();
        $date->setTimezone($this->timezone);

        $year = (int) $date->format('Y');

        $this->year_min = $year + $min;
        $this->year_max = $year + $max;
    }

    /**
     * Attempt to parse the given form input and convert to integer timestamp.
     *
     * @param string $input
     *
     * @return int|null integer timestamp on success; NULL on failure
     */
    public function parseInput($input)
    {
        if (!isset($input[self::KEY_YEAR], $input[self::KEY_MONTH], $input[self::KEY_DAY])) {
            return null;
        }

        $year = (int) $input[self::KEY_YEAR];
        $month = (int) $input[self::KEY_MONTH];
        $day = (int) $input[self::KEY_DAY];

        if (! checkdate($month, $day, $year)) {
            return null; // invalid date
        }

        $date = new DateTime();
        $date->setTimezone($this->timezone);
        $date->setDate($year, $month, $day);
        $date->setTime(0, 0, 0);

        return $date->getTimestamp();
    }

    /**
     * @param InputModel $model
     *
     * @return int|null timestamp
     *
     * @throws UnexpectedValueException if the input is invalid (assumes valid input)
     */
    public function getValue(InputModel $model)
    {
        $input = $model->getInput($this);

        if ($input === null) {
            return null;
        }

        $value = $this->parseInput($input);

        if ($value === null) {
            throw new UnexpectedValueException("invalid date input");
        }

        return $value;
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
        if (is_int($value)) {
            $date = new Datetime();
            $date->setTimestamp($value);
            $date->setTimezone($this->timezone);

            $model->setInput(
                $this, array(
                    self::KEY_YEAR  => $date->format('Y'),
                    self::KEY_MONTH => $date->format('n'),
                    self::KEY_DAY   => $date->format('j'),
                )
            );
        } elseif ($value === null) {
            $model->setInput($this, null);
        } else {
            throw new InvalidArgumentException("string expected");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        $years = range($this->year_min, $this->year_max);
        $months = range(1, 12);
        $days = range(1, 31);

        $date_model = InputModel::create($model->getInput($this));

        $year = new SelectField(self::KEY_YEAR, array_combine($years, $years));
        $month = new SelectField(self::KEY_MONTH, array_combine($months, $this->months));
        $day = new SelectField(self::KEY_DAY, array_combine($days, $days));

        $html = $year->renderInput($renderer, $date_model, array('class' => $this->year_class))
            . $month->renderInput($renderer, $date_model, array('class' => $this->month_class))
            . $day->renderInput($renderer, $date_model, array('class' => $this->day_class));

        return $html;
    }
}
