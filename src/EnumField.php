<?php

namespace mindplay\kissform;

/**
 * This class provides information about a field
 * with an enumerated set of permitted value options.
 */
class EnumField extends Field implements HasOptions
{
    /** @var string[] hash, where value maps to value label */
    public $options;

    /**
     * @see HasOptions::getOptions()
     */
    public function getOptions()
    {
        return $this->options;
    }
}
