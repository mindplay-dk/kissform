<?php

namespace mindplay\kissform;

/**
 * This class provides information about an integer field.
 */
class IntField extends TextField
{
    /** @var int|null minimum value */
    public $min_value;

    /** @var int|null maximum value */
    public $max_value;
}
