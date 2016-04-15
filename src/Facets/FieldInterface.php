<?php
/**
 * Created by PhpStorm.
 * User: Rasmus Schultz
 * Date: 1/12/2016
 * Time: 11:19 AM
 */
namespace mindplay\kissform\Facets;

use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;

/**
 * This interface defines the minimum public interface required of field implementations.
 *
 * The {@see Field} base class implements both getters and setters, and is your most likely
 * starting point for new Field types.
 */
interface FieldInterface
{
    /**
     * @return string Field name
     */
    public function getName();

    /**
     * @return string|null display label (or NULL, if no label is defined)
     */
    public function getLabel();

    /**
     * @return string|null placeholder label (or NULL, if no placeholder is defined)
     */
    public function getPlaceholder();

    /**
     * @return bool TRUE, if input is required for this Field; FALSE, if it's optional
     */
    public function isRequired();

    /**
     * @param InputModel $model
     *
     * @return string|null
     */
    public function getValue(InputModel $model);

    /**
     * @return ValidatorInterface[] list of default validators for this Field
     */
    public function createValidators();

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
