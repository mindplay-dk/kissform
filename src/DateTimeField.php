<?php

namespace mindplay\kissform;

use DateTimeZone;

/**
 * This class implements a date/time input intended for use with a client-side UI library.
 *
 * You should specify the {@link $format} and call {@link setTimeZone()} - by default,
 * the timezone is obtained from {@date_default_timezone_get()} which is system-dependent.
 *
 * Format string is similar that of {@link date()} - see documentation below:
 *
 * {@link http://php.net/manual/en/datetime.createfromformat.php#refsect1-datetime.createfromformat-parameters}
 */
class DateTimeField extends DateTimeStringField
{
    /**
     * @param string $name field name
     * @param DateTimeZone|string|null $timezone input time-zone (or NULL to use the current default timezone)
     */
    public function __construct($name, $timezone = null)
    {
        parent::__construct($name);

        $this->attrs['data-ui'] = 'datetimepicker';
        $this->format = 'Y-m-d H:i:s';

        $this->setTimeZone($timezone);
    }
}
