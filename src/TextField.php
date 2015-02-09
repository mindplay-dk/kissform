<?php

namespace mindplay\kissform;

/**
 * This class provides information about a text field, e.g. a plain
 * input type=text or textarea element.
 */
class TextField extends Field
{
    /** @var int|null */
    public $min_length;

    /** @var int|null */
    public $max_length;
}
