<?php

namespace mindplay\kissform;

/**
 * This interface is implemented by Field types that provide option values,
 * e.g. for fields presented as a drop-down select or group of radio buttons.
 *
 * @see FormHelper::select()
 */
interface HasOptions
{
    /** @return string[] map where option value => option label */
    public function getOptions();
}
