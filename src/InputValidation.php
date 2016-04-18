<?php

namespace mindplay\kissform;

use mindplay\kissform\Facets\FieldInterface;
use mindplay\kissform\Facets\ValidatorInterface;

/**
 * This class represents the validation state of an input model.
 *
 * Errors accumulate internally - only the first error encountered (for a given property)
 * is recorded, since commonly multiple error-messages for the same property are
 * of no practical help to the end-user.
 *
 * By default {@see Field::$label} is used when referring to fields in error messages,
 * but you can override these names using {@see InputValidation::setTitle()}.
 */
class InputValidation
{
    /**
     * @var InputModel input model
     */
    protected $model;

    /**
     * @var string[] map where field name => display name override
     */
    protected $titles = [];

    /**
     * The given input/model is assumed to be valid
     *
     * @param InputModel|array|null $model the form input to be validated
     */
    public function __construct($model)
    {
        $this->model = InputModel::create($model);

        $this->model->clearErrors(true);
    }

    /**
     * Produce a title for a given Field.
     *
     * @param FieldInterface $field
     *
     * @return string label
     *
     * @see setLabel()
     * @see Field::getLabel()
     */
    public function getLabel(FieldInterface $field)
    {
        return array_key_exists($field->getName(), $this->titles)
            ? $this->titles[$field->getName()]
            : $field->getLabel();
    }

    /**
     * Override the display name used when referring to a given Field in error messages
     *
     * @param FieldInterface $field
     * @param string         $title display name override
     *
     * @return $this
     *
     * @see getLabel()
     */
    public function setLabel(FieldInterface $field, $title)
    {
        $this->titles[$field->getName()] = $title;

        return $this;
    }

    /**
     * Check the basic constraints defined by the Field itself, validating for e.g. required input,
     * data-types and value ranges.
     *
     * @param FieldInterface|FieldInterface[] $field Field(s) to check
     *
     * @return $this
     *
     * @see Field::createValidators()
     */
    public function check($field)
    {
        /** @var FieldInterface[] $fields */
        $fields = is_array($field) ? $field : [$field];

        foreach ($fields as $field) {
            $this->validate($field, $field->createValidators());
        }

        return $this;
    }

    /**
     * Validates one or more Fields using one or more given Validators.
     *
     * Consider calling {@see check()} first to validate the Field's basic constraints.
     *
     * @param FieldInterface|FieldInterface[]         $field     Field(s) to validate
     * @param ValidatorInterface|ValidatorInterface[] $validator one or more Validators to apply
     *
     * @return $this
     *
     * @see check()
     */
    public function validate($field, $validator)
    {
        /** @var FieldInterface[] $fields */
        $fields = is_array($field) ? $field : [$field];

        foreach ($fields as $field) {
            /** @var ValidatorInterface[] $validators */
            $validators = is_array($validator) ? $validator : [$validator];

            foreach ($validators as $validator) {
                $validator->validate($field, $this->model, $this);
            }
        }

        return $this;
    }
}
