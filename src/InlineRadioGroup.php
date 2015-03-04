<?php

namespace mindplay\kissform;

/**
 * Variation of RadioField intended for inline (horizontal) layout of checkboxes.
 */
class InlineRadioGroup extends RadioGroup
{
    /**
     * @var string|null wrapper tag (e.g. "div", or NULL to disable wrapper-tags)
     */
    public $wrapper_tag = null;

    /**
     * @var string[] map of HTML attributes for the <label> tag
     */
    public $label_attr = array('class' => 'radio-inline');
}
