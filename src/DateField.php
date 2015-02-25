<?php

namespace mindplay\kissform;

use DateTimeZone;

class DateField extends BaseDateTimeField
{
    /**
     * @param string $name field name
     * @param DateTimeZone|string|null $timezone input time-zone (or NULL to use the current default timezone)
     */
    public function __construct($name, $timezone = null)
    {
        parent::__construct($name);

        $this->attrs['data-ui'] = 'datepicker';
        $this->format = 'Y-m-d';

        $this->setTimeZone($timezone);
    }
}
