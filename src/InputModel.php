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
    public $errors;

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
        if (!isset($this->input[$field->name])) {
            return null;
        }

        if (is_scalar($this->input[$field->name])) {
            return (string)$this->input[$field->name];
        }

        return $this->input[$field->name];
    }

    /**
     * @param Field        $field
     * @param string|array $value
     *
     * @return void
     */
    public function setInput(Field $field, $value)
    {
        $this->input[$field->name] = $value;
    }

    /**
     * Set an error message for a given Field, if one is not already set for that
     * Field - we only care about the first error message for each Field, so add
     * error messages in order of importance.
     *
     * @param Field  $field the field for which to set an error-message
     * @param string $error error message
     *
     * @return void
     */
    public function setError(Field $field, $error)
    {
        if (! isset($this->errors[$field->name])) {
            $this->errors[$field->name] = $error;
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
     * @return bool true, if the form contains any error(s)
     */
    public function hasErrors()
    {
        return count($this->errors) !== 0;
    }

    /**
     * Clears any accummulated error messages
     *
     * @return void
     */
    public function clearErrors()
    {
        $this->errors = array();
    }
}
