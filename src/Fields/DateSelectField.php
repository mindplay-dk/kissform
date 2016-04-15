<?php

namespace mindplay\kissform\Fields;

use DateTime;
use DateTimeZone;
use InvalidArgumentException;
use mindplay\kissform\Facets\ParserInterface;
use mindplay\kissform\Fields\Base\TimeZoneAwareField;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\Validators\CheckDateTime;
use mindplay\lang;
use UnexpectedValueException;

/**
 * This field type implements a date-picker using three (year/month/day) drop-downs.
 */
class DateSelectField extends TimeZoneAwareField implements ParserInterface
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
    public $months;

    /**
     * @var string label for the year drop-down
     */
    public $label_year;

    /**
     * @var string label for the year drop-down
     */
    public $label_month;

    /**
     * @var string label for the year drop-down
     */
    public $label_day;

    /**
     * @var string[] field order, e.g. KEY_* constants in the desired input order
     */
    public $order = [
        self::KEY_DAY,
        self::KEY_MONTH,
        self::KEY_YEAR,
    ];

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
        
        $t = lang::domain("mindplay/kissform");
        
        $this->months = explode('|', $t("months"));

        $this->label_year = $t("year");
        $this->label_month = $t("month");
        $this->label_day = $t("day");
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
                $this, [
                    self::KEY_YEAR  => $date->format('Y'),
                    self::KEY_MONTH => $date->format('n'),
                    self::KEY_DAY   => $date->format('j'),
                ]
            );
        } elseif ($value === null) {
            $model->setInput($this, null);
        } else {
            throw new InvalidArgumentException("string expected");
        }
    }

    /**
     * @inheritdoc
     *
     * @throws UnexpectedValueException
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        $years = range($this->year_min, $this->year_max);
        $months = range(1, 12);
        $days = range(1, 31);

        $year = new SelectField(self::KEY_YEAR, array_combine($years, $years));
        $month = new SelectField(self::KEY_MONTH, array_combine($months, $this->months));
        $day = new SelectField(self::KEY_DAY, array_combine($days, $days));

        if (! $this->isRequired()) {
            $year->disabled = $this->label_year;
            $month->disabled = $this->label_month;
            $day->disabled = $this->label_day;
        }

        $html = '';

        $renderer->visit($this, function () use ($renderer, $year, $month, $day, &$html) {
            foreach ($this->order as $key) {
                switch ($key) {
                    case DateSelectField::KEY_YEAR:
                        $html .= $renderer->render($year, ['class' => $this->year_class]);
                        break;

                    case DateSelectField::KEY_MONTH:
                        $html .= $renderer->render($month, ['class' => $this->month_class]);
                        break;

                    case DateSelectField::KEY_DAY:
                        $html .= $renderer->render($day, ['class' => $this->day_class]);
                        break;

                    default:
                        throw new UnexpectedValueException("expected 'year', 'month' or 'day' - got: {$key}");
                }
            }
        });

        return $html;
    }

    /**
     * {@inheritdoc}
     */
    public function createValidators()
    {
        $validators = parent::createValidators();

        $validators[] = new CheckDateTime($this);

        return $validators;
    }
}
