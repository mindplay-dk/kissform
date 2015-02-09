<?php

namespace mindplay\kissform;

use RuntimeException;

/**
 * Simple form state validator.
 *
 * Errors accumulate in the public {@see $errors} property, indexed by name - only
 * the first error encountered (for a given property) is recorded, since typically
 * multiple error-messages for the same property are of no use to anyone.
 *
 * By default {@see Field::$label} is used when referring to fields in error messages,
 * but you can override these names using {@see title()}.
 *
 * @property-read bool $valid   true, if no errors have been recorded; otherwise false.
 * @property-read bool $invalid true, if errors have been recorded; otherwise false.
 */
class FormValidator
{
    /**
     * @var array form state (maps of strings, possibly nested)
     */
    public $state;

    /**
     * @var string[] error messages indexed by field name
     */
    public $errors = array();

    /**
     * @var string[] map where field name => display name
     */
    public $titles = array();

    /**
     * @param array $state the form state to be validated
     */
    public function __construct(array $state)
    {
        $this->state = $state;
    }

    /**
     * @param Field $field
     *
     * @return string|null value (or NULL if no value exists in $state)
     */
    protected function getValue(Field $field)
    {
        return isset($this->state[$field->name])
            ? (string)$this->state[$field->name]
            : null;
    }

    /**
     * Read accessors (see <code>@property</code> annotations)
     *
     * @param string $name
     *
     * @return mixed
     *
     * @throws RuntimeException on attempted access to undefined property
     *
     * @ignore
     */
    public function __get($name)
    {
        switch ($name) {
            case 'valid':
                return count($this->errors) === 0;

            case 'invalid':
                return count($this->errors) > 0;

            default:
                throw new RuntimeException("undefined property \${$name}");
        }
    }

    /**
     * Produce a title for a given property.
     *
     * @param Field $field
     *
     * @return string label
     *
     * @see name()
     * @see Field::$label
     */
    protected function getTitle(Field $field)
    {
        return isset($this->titles[$field->name])
            ? $this->titles[$field->name]
            : $field->label;
    }

    /**
     * Change the display name used when referring to a given Field in error messages
     *
     * @param Field  $field
     * @param string $title display name
     *
     * @return $this
     *
     * @see getTitle()
     */
    public function title(Field $field, $title)
    {
        $this->titles[$field->name] = $title;

        return $this;
    }

    /**
     * Clear the current error message for a given Field
     *
     * @param Field $field Field to clear error message for
     *
     * @return $this
     */
    public function clear(Field $field)
    {
        unset($this->errors[$field->name]);

        return $this;
    }

    /**
     * Reset all accumulated error messages (for all fields)
     *
     * @return $this
     */
    public function reset()
    {
        $this->errors = array();

        return $this;
    }

    /**
     * Automatically perform basic validations on the given fields, based
     * on the type and property values of that Field.
     *
     * @see Field::$required
     * @see BoolField
     * @see EnumField
     * @see HasOptions
     * @see IntField::$min_value
     * @see IntField::$max_value
     * @see TextField::$min_length
     * @see TextField::$max_length
     *
     * @param Field ...$field
     *
     * @return $this
     */
    public function check(Field $field)
    {
        // TODO

        return $this;
    }

    /**
     * Set an error message for a given field, if one is not already set for that
     * property - we only care about the first error message for each field.
     *
     * @param Field  $field
     * @param string $error    error message template, compatible with sprintf()
     * @param mixed  ...$value values to substitute in error message template
     *
     * @throws RuntimeException for invalid number of arguments
     *
     * @return $this
     */
    public function error(Field $field, $error)
    {
        if (isset($this->errors[$field->name])) {
            return $this; // ignore error - the first error for this field was already recorded
        }

        $params = func_get_args();

        array_shift($params); // remove $field from params

        $this->errors[$field->name] = call_user_func_array('sprintf', $params);

        return $this;
    }

    /**
     * Validate required input.
     *
     * @param Field  $field
     * @param string $error error message template
     *
     * @return $this
     */
    public function required(Field $field, $error = '%s is required')
    {
        if ($this->getValue($field) == '') {
            $this->error($field, $error, $this->getTitle($field));
        }

        return $this;
    }

    /**
     * Validate repeated input (e.g. confirming e-mail address or password, etc.)
     *
     * @param Field  $field
     * @param Field  $confirm_field other Field (for comparison)
     * @param string $error         error message template
     *
     * @return $this
     */
    public function confirm(Field $field, $confirm_field, $error = '%s must match %s')
    {
        if ($this->getValue($field) !== $this->getValue($confirm_field)) {
            $this->error($confirm_field, $error, $this->getTitle($field), $this->getTitle($confirm_field));
        }

        return $this;
    }

    /**
     * Validate numeric input.
     *
     * @param Field  $field
     * @param string $error error message template
     *
     * @return $this
     */
    public function numeric(Field $field, $error = '%s should be a number')
    {
        if (!is_numeric($this->getValue($field))) {
            $this->error($field, $error, $this->getTitle($field));
        }

        return $this;
    }

    /**
     * Validate numeric input, allowing floating point values.
     *
     * @param Field  $field
     * @param string $error error message template
     *
     * @return $this
     */
    public function float(Field $field, $error = '%s should be a number')
    {
        $value = $this->getValue($field);

        if (!(is_numeric($value) || preg_match('/^\d+\.\d+$/', $value) === 1)) {
            $this->error($field, $error, $this->getTitle($field));
        }

        return $this;
    }

    /**
     * Validate e-mail address.
     *
     * @param Field  $field
     * @param string $error error message template
     *
     * @return $this
     */
    public function email(Field $field, $error = '%s must be a valid e-mail address')
    {
        if (filter_var($this->getValue($field), FILTER_VALIDATE_EMAIL) === false) {
            $this->error($field, $error, $this->getTitle($field));
        }

        return $this;
    }

    /**
     * Validate min/max length of a string.
     *
     * @param Field  $field
     * @param int    $min   minimum number of chars
     * @param int    $max   maximum number of chars
     * @param string $error error message template
     *
     * @return $this
     */
    public function length(
        Field $field,
        $min = null,
        $max = null,
        $error = '%s must be between %d and %d characters long'
    ) {
        if ($field instanceof TextField) {
            $min = $min ?: $field->min_length;
            $max = $max ?: $field->max_length;
        }

        $length = strlen($this->getValue($field));

        if ($length < $min || $length > $max) {
            $this->error($field, $error, $this->getTitle($field), $min, $max);
        }

        return $this;
    }

    /**
     * Validate input matching a regular expression.
     *
     * @param Field  $field
     * @param string $pattern  regular expression pattern to match
     * @param string $error    error message template (compatible with sprintf)
     * @param mixed  ...$value values to substitute in error message template
     *
     * @return $this
     */
    public function match(Field $field, $pattern, $error)
    {
        $params = func_get_args();

        array_splice($params, 1, 1); // remove $pattern argument

        if (!preg_match($pattern, $this->getValue($field))) {
            call_user_func_array(array($this, 'error'), $params);
        }

        return $this;
    }

    /**
     * Basic password validation: must include lowercase and uppercase or numeric characters.
     *
     * @param Field  $field
     * @param string $error error message
     *
     * @return $this
     *
     * @see length()
     */
    public function password(Field $field, $error = 'This password is not secure')
    {
        return $this->match($field, "#.*^(?=.*[a-z])(?=.*[A-Z0-9]).*$#", $error);
    }

    /**
     * Validate a required checkbox (for confirmations, e.g. accepted privacy policy or terms of service)
     *
     * @param BoolField $field
     * @param string    $error error message
     *
     * @return $this
     *
     * @see $checked_values
     */
    public function checked(BoolField $field, $error = 'Please confirm by ticking the %s checkbox')
    {
        if ($this->getValue($field) != $field->checked_value) {
            $this->error($field, $error, $this->getTitle($field));
        }

        return $this;
    }
}
