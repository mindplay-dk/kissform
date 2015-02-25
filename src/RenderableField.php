<?php

namespace mindplay\kissform;

/**
 * This interface is implemented by Field-types that provide a default (built-in)
 * template for rendering an appropriate HTML input.
 */
interface RenderableField
{
    /**
     * Use the given InputRenderer to render an HTML input for this Field, using
     * state obtained from the given InputModel, and optionally overriding a given
     * set of HTML attribute values.
     *
     * @param InputRenderer $renderer
     * @param InputModel    $model
     * @param string[]      $attr map of HTML attributes
     *
     * @return string HTML
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr);
}
