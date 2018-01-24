<?php

namespace mindplay\kissform\Fields;

use mindplay\kissform\InputModel;

/**
 * Date/time field-type for string-based input using HTML5 `<input type="datetime-local">`.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/datetime-local
 */
class DateTimeLocalField extends DateTimeField
{
    const HTML5_FORMAT = "Y-m-d\\TH:i";

    /**
     * @param string $name     field name
     * @param string $timezone timezone name
     * @param array  $attrs    map of HTML attribtues to apply
     */
    public function __construct($name, $timezone, $attrs = [])
    {
        $attrs += [
            "type" => "datetime-local",
        ];

        parent::__construct($name, $timezone, self::HTML5_FORMAT, $attrs);
    }
}
