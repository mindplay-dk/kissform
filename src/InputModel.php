<?php

namespace mindplay\kissform;

use mindplay\kissform\Facets\FieldInterface;

/**
 * This model represents form state: input values and errors.
 */
class InputModel
{
    /**
     * @var array form input (maps of strings, possibly nested)
     */
    public $input;

    /**
     * @var string[] map where field name => error message
     */
    protected $errors;

    /**
     * @var bool true, if any validation has been performed
     */
    protected $validated = false;

    /**
     * @param array    $input  map where field name => input value(s)
     * @param string[] $errors map where field name => error message
     */
    public function __construct(array $input, array $errors)
    {
        $this->input = $input;
        $this->errors = $errors;
    }

    /**
     * @param InputModel|array|null $input  map where field name => input value(s)
     * @param string[]              $errors map where field name => error message
     *
     * @return self
     */
    public static function create($input = null, $errors = null)
    {
        if ($input instanceof self) {
            return $input; // InputModel instance given
        }

        return new self($input ?: [], $errors ?: []);
    }

    /**
     * @param FieldInterface|string $field
     *
     * @return string|array|null value (or NULL, if no value exists in $input)
     */
    public function getInput($field)
    {
        $name = $field instanceof FieldInterface
            ? $field->getName()
            : (string) $field;

        if (!isset($this->input[$name]) || $this->input[$name] === '') {
            return null;
        }

        if (is_scalar($this->input[$name])) {
            return (string) $this->input[$name];
        }

        return $this->input[$name];
    }

    /**
     * @param FieldInterface|string $field
     * @param string|array|null     $value
     *
     * @return void
     */
    public function setInput($field, $value)
    {
        $name = $field instanceof FieldInterface
            ? $field->getName()
            : (string) $field;

        if ($value === null || $value === '' || $value === []) {
            unset($this->input[$name]);
        } else {
            $this->input[$name] = is_array($value)
                ? $value
                : (string) $value;
        }
    }

    /**
     * Get all accummulated error-messages, indexed by Field-name.
     *
     * @return string[] map where field-name => error message
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Get the error message for a given Field.
     *
     * @param FieldInterface|string $field
     *
     * @return string|string[]|null error-message (or NULL, if the given Field has no error)
     */
    public function getError($field)
    {
        return @$this->errors[$field instanceof FieldInterface ? $field->getName() : (string) $field];
    }

    /**
     * Set an error message for a given Field, if one is not already set for that
     * Field - we only care about the first error message for each Field, so add
     * error messages in order of importance.
     *
     * @param FieldInterface|string $field the field for which to set an error-message
     * @param string                $error error message
     *
     * @return void
     */
    public function setError($field, $error)
    {
        $name = $field instanceof FieldInterface
            ? $field->getName()
            : (string) $field;

        if (! isset($this->errors[$name])) {
            $this->errors[$name] = $error;
        }
    }

    /**
     * @param FieldInterface|string $field
     *
     * @return bool true, if the given Field has an error message
     *
     * @see $errors
     */
    public function hasError($field)
    {
        return isset($this->errors[$field instanceof FieldInterface ? $field->getName() : (string) $field]);
    }

    /**
     * Clear the current error message for a given Field
     *
     * @param FieldInterface|string $field Field to clear error message for
     *
     * @return void
     */
    public function clearError($field)
    {
        unset($this->errors[$field instanceof FieldInterface ? $field->getName(): (string) $field]);
    }

    /**
     * Check the model for errors - this does not take into account whether the
     * form has been validated or not.
     *
     * @return bool true, if the form contains any error(s)
     *
     * @see isValid()
     */
    public function hasErrors()
    {
        return count($this->errors) !== 0;
    }

    /**
     * Check if the model has been validated and contains no errors.
     *
     * @return bool true, if the form has been validated and contains no errors.
     *
     * @see hasErrors()
     */
    public function isValid()
    {
        return $this->validated && ! $this->hasErrors();
    }

    /**
     * Clears any accumulated error messages and marks the model as either
     * non-validated (default) or validated.
     *
     * @param bool $validated true, if the model has been validated
     *
     * @return void
     */
    public function clearErrors($validated = false)
    {
        $this->errors = [];

        $this->validated = $validated;
    }
}
