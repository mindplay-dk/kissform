<?php

namespace mindplay\kissform;

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

        return new self($input ?: array(), $errors ?: array());
    }

    /**
     * @param Field $field
     *
     * @return string|array|null value (or NULL, if no value exists in $input)
     */
    public function getInput(Field $field)
    {
        if (!isset($this->input[$field->name]) || $this->input[$field->name] === '') {
            return null;
        }

        if (is_scalar($this->input[$field->name])) {
            return (string)$this->input[$field->name];
        }

        return $this->input[$field->name];
    }

    /**
     * @param Field             $field
     * @param string|array|null $value
     *
     * @return void
     */
    public function setInput(Field $field, $value)
    {
        if ($value === null || $value === '' || $value === array()) {
            unset($this->input[$field->name]);
        } else {
            $this->input[$field->name] = is_array($value) ? $value : (string) $value;
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
     * @param Field|string $field
     *
     * @return string|string[]|null error-message (or NULL, if the given Field has no error)
     */
    public function getError($field)
    {
        return @$this->errors[$field instanceof Field ? $field->name : (string) $field];
    }

    /**
     * Set an error message for a given Field, if one is not already set for that
     * Field - we only care about the first error message for each Field, so add
     * error messages in order of importance.
     *
     * @param Field|string    $field the field for which to set an error-message
     * @param string|string[] $error error message (or map of error-messages
     *
     * @return void
     */
    public function setError($field, $error)
    {
        $name = $field instanceof Field
            ? $field->name
            : (string) $field;

        if (! isset($this->errors[$name])) {
            $this->errors[$name] = $error;
        }
    }

    /**
     * @param Field $field
     *
     * @return bool true, if the given Field has an error message
     *
     * @see $errors
     */
    public function hasError(Field $field)
    {
        return isset($this->errors[$field->name]);
    }

    /**
     * Clear the current error message for a given Field
     *
     * @param Field $field Field to clear error message for
     */
    public function clearError(Field $field)
    {
        unset($this->errors[$field->name]);
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
        $this->errors = array();

        $this->validated = $validated;
    }
}
