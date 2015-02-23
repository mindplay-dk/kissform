<?php

namespace mindplay\kissform;

use DateTime;
use RuntimeException;

/**
 * Simple form input validator.
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
class InputValidator
{
    /**
     * @var InputModel input model
     */
    public $model;

    /**
     * @var string[] map where field name => display name
     */
    public $titles = array();

    /**
     * Default, localized validation error messages.
     *
     * @var string[] map where validation method-name => validation error-message
     */
    public $lang = array(
        'required'  => '{field} is required',
        'confirm'   => '{field} must match {confirm_field}',
        'int'       => '{field} must be a whole number',
        'numeric'   => '{field} must be a number',
        'email'     => '{field} must be a valid e-mail address',
        'length'    => '{field} must be between {min} and {max} characters long',
        'minLength' => '{field} must be at least {min} characters long',
        'maxLength' => '{field} must be at most {max} characters long',
        'range'     => '{field} must be between {min} and {max}',
        'minValue'  => '{field} must be at least {min}',
        'maxValue'  => '{field} must be at most {max}',
        'password'  => 'This password is not secure',
        'checked'   => 'Please confirm by ticking the {field} checkbox',
        'selected'  => 'Please select {field} from the list of available options',
        'datetime'  => '{field} must be a valid date/time',
        'token'     => 'You may be submitting this form too quickly - please wait {time} seconds and try again...',
    );

    /**
     * @var string regular expression used for password validation
     *
     * @see password()
     */
    public $password_pattern = "#.*^(?=.*[a-z])(?=.*[A-Z0-9]).*$#";

    /**
     * @param InputModel|array|null $model the form input to be validated
     */
    public function __construct($model)
    {
        $this->model = InputModel::create($model);
    }

    /**
     * @param Field $field
     *
     * @return string|array|null value (or NULL, if no value exists in $input)
     */
    protected function getInput(Field $field)
    {
        return $this->model->getInput($field);
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
                return $this->model->hasErrors() === false;

            case 'invalid':
                return $this->model->hasErrors();

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
     * Reset all accumulated error messages (for all fields)
     *
     * @return $this
     */
    public function reset()
    {
        $this->model->clearErrors();

        return $this;
    }

    /**
     * Automatically perform basic validations on the given Fields, based
     * on the types and properties of the given Fields - this includes
     * checking for required input, {@link BoolField} must be checked,
     * {@link HasOptions} fields must have a valid selection, {@link IntField}
     * must be within {@link IntField::$min_value} and {@link IntField::$max_value}
     * and {@link TextField} must have a length between {@link TextField::$min_length}
     * and {@link TextField::$max_length}.
     *
     * @param Field|Field[] ...$field
     *
     * @return $this
     *
     * @see Field::$required
     * @see BoolField
     * @see EnumField
     * @see HasOptions
     * @see IntField::$min_value
     * @see IntField::$max_value
     * @see TextField::$min_length
     * @see TextField::$max_length
     */
    public function validate($field)
    {
        $args = func_get_args();

        if (count($args) > 1) {
            return $this->validate($args);
        }

        if (is_array($args[0])) {
            foreach ($args[0] as $field) {
                $this->validate($field);
            }

            return $this;
        }

        if ($field->required) {
            $this->required($field);
        }

        if ($field instanceof BoolField) {
            $this->checked($field);
        }

        if ($field instanceof HasOptions) {
            $this->selected($field);
        }

        if ($field instanceof IntField) {
            if ($field->min_value !== null) {
                if ($field->max_value !== null) {
                    $this->range($field);
                } else {
                    $this->minValue($field);
                }
            } else if ($field->max_value !== null) {
                $this->maxValue($field);
            }
        }

        if ($field instanceof TextField) {
            if ($field->min_length !== null) {
                if ($field->max_length !== null) {
                    $this->length($field);
                } else {
                    $this->minLength($field);
                }
            } else if ($field->max_length !== null) {
                $this->maxLength($field);
            }
        }

        return $this;
    }

    /**
     * Set an error message for a given Field, if one is not already set for that
     * Field - we only care about the first error message for each Field, so add
     * error messages (and perform validations) in order of importance.
     *
     * Any {name} placeholders in the template will be substituted with $values.
     *
     * Note that the field title (as provided by {@link getTitle()}) is always
     * available for substitution using the {field} placeholder.
     *
     * @param Field    $field
     * @param string   $template error message template, using {name} placeholders
     * @param string[] $values   map where placeholder => value (optional)
     *
     * @return $this
     */
    public function error(Field $field, $template, array $values = array())
    {
        if ($this->model->hasError($field)) {
            return $this; // ignore error - the first error for this field was already recorded
        }

        $__template = $template;
        $__field = $field;

        unset($template, $field);

        if (!isset($values['field'])) {
            /** @noinspection PhpUnusedLocalVariableInspection local variable used for templating below */
            $field = $this->getTitle($__field);
        }

        extract($values);

        $this->model->setError($__field, preg_replace('/\{([^\{]{1,100}?)\}/e', "$$1", $__template));

        return $this;
    }

    /**
     * Validate input matching a regular expression.
     *
     * @param Field    $field
     * @param string   $pattern  regular expression pattern to match
     * @param string   $error    error message template, using {name} placeholders
     * @param string[] $values   map where placeholder => value (optional)
     *
     * @return $this
     */
    public function match(Field $field, $pattern, $error, array $values = array())
    {
        if (!preg_match($pattern, $this->getInput($field))) {
            $this->error($field, $error, $values);
        }

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
    public function required(Field $field, $error = null)
    {
        if ($this->getInput($field) == '') {
            $this->error($field, $error ?: $this->lang[__FUNCTION__]);
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
    public function confirm(Field $field, $confirm_field, $error = null)
    {
        if ($this->getInput($field) !== $this->getInput($confirm_field)) {
            $this->error(
                $confirm_field,
                $error ?: $this->lang[__FUNCTION__],
                array('confirm_field' => $this->getTitle($confirm_field)));
        }

        return $this;
    }

    /**
     * Validate whole number input.
     *
     * @param Field  $field
     * @param string $error error message template
     *
     * @return $this
     */
    public function int(Field $field, $error = null)
    {
        if (preg_match('/^\-?\d+$/', $this->getInput($field)) !== 1) {
            $this->error($field, $error ?: $this->lang[__FUNCTION__]);
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
    public function numeric(Field $field, $error = null)
    {
        $value = $this->getInput($field);

        if (!(is_numeric($value) || preg_match('/^\d+\.\d+$/', $value) === 1)) {
            $this->error($field, $error ?: $this->lang[__FUNCTION__]);
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
    public function email(Field $field, $error = null)
    {
        if (filter_var($this->getInput($field), FILTER_VALIDATE_EMAIL) === false) {
            $this->error($field, $error ?: $this->lang[__FUNCTION__]);
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
    public function length(Field $field, $min = null, $max = null, $error = null)
    {
        if ($field instanceof TextField) {
            $min = $min ?: $field->min_length;
            $max = $max ?: $field->max_length;
        }

        $length = strlen($this->getInput($field));

        if ($length < $min || $length > $max) {
            $this->error($field, $error ?: $this->lang[__FUNCTION__], array('min' => $min, 'max' => $max));
        }

        return $this;
    }

    /**
     * Validate minimum length of a string.
     *
     * @param Field  $field
     * @param int    $min   minimum number of chars
     * @param string $error error message template
     *
     * @return $this
     */
    public function minLength(Field $field, $min = null, $error = null)
    {
        if ($field instanceof TextField) {
            $min = $min ?: $field->min_length;
        }

        $length = strlen($this->getInput($field));

        if ($length < $min) {
            $this->error($field, $error ?: $this->lang[__FUNCTION__], array('min' => $min));
        }

        return $this;
    }

    /**
     * Validate maximum length of a string.
     *
     * @param Field  $field
     * @param int    $max   maximum number of chars
     * @param string $error error message template
     *
     * @return $this
     */
    public function maxLength(Field $field, $max = null, $error = null)
    {
        if ($field instanceof TextField) {
            $max = $max ?: $field->max_length;
        }

        $length = strlen($this->getInput($field));

        if ($length > $max) {
            $this->error($field, $error ?: $this->lang[__FUNCTION__], array('max' => $max));
        }

        return $this;
    }

    /**
     * Validate numerical value within a min/max range.
     *
     * @param Field     $field
     * @param int|float $min minimum allowed value (inclusive)
     * @param int|float $max maximum allowed value (inclusive)
     * @param string    $error
     *
     * @return $this
     */
    public function range(Field $field, $min = null, $max = null, $error = null)
    {
        $this->numeric($field);

        if ($this->model->hasError($field)) {
            return $this;
        }

        if ($min === null || $max === null) {
            if ($field instanceof IntField) {
                $min = $field->min_value;
                $max = $field->max_value;
            } else {
                throw new RuntimeException("no min/max value provided");
            }
        }

        $value = $this->getInput($field);

        if ($value < $min || $value > $max) {
            $this->error($field, $error ?: $this->lang[__FUNCTION__], array('min' => $min, 'max' => $max));
        }

        return $this;
    }

    /**
     * Validate numerical value with a maximum.
     *
     * @param Field     $field
     * @param int|float $max maximum allowed value (inclusive)
     * @param string    $error
     *
     * @return $this
     */
    public function maxValue(Field $field, $max = null, $error = null)
    {
        $this->numeric($field);

        if ($this->model->hasError($field)) {
            return $this;
        }

        if ($max === null) {
            if ($field instanceof IntField) {
                $max = $field->max_value;
            } else {
                throw new RuntimeException("no maximum value provided");
            }
        }

        $value = $this->getInput($field);

        if ($value > $max) {
            $this->error($field, $error ?: $this->lang[__FUNCTION__], array('max' => $max));
        }

        return $this;
    }

    /**
     * Validate numerical value with a minimum.
     *
     * @param Field     $field
     * @param int|float $min minimum allowed value (inclusive)
     * @param string    $error
     *
     * @return $this
     */
    public function minValue(Field $field, $min = null, $error = null)
    {
        $this->numeric($field);

        if ($this->model->hasError($field)) {
            return $this;
        }

        if ($min === null) {
            if ($field instanceof IntField) {
                $min = $field->min_value;
            } else {
                throw new RuntimeException("no minimum value provided");
            }
        }

        $value = $this->getInput($field);

        if ($value < $min) {
            $this->error($field, $error ?: $this->lang[__FUNCTION__], array('min' => $min));
        }

        return $this;
    }

    /**
     * Basic password validation: must include lowercase and uppercase or numeric characters.
     *
     * Use {@link length()} or {@link minLength()} or {@link maxLength()} to validate the length,
     * or customize the {@link $password_pattern} as needed.
     *
     * @param Field  $field
     * @param string $error error message
     *
     * @return $this
     *
     * @see $password_pattern
     */
    public function password(Field $field, $error = null)
    {
        return $this->match($field, $this->password_pattern, $error ?: $this->lang[__FUNCTION__]);
    }

    /**
     * Validate a required checkbox (for confirmations, e.g. accepted privacy policy or terms of service)
     *
     * @param BoolField $field
     * @param string    $error error message
     *
     * @return $this
     *
     * @see Field::$checked_value
     */
    public function checked(BoolField $field, $error = null)
    {
        if ($this->getInput($field) != $field->checked_value) {
            $this->error($field, $error ?: $this->lang[__FUNCTION__]);
        }

        return $this;
    }

    /**
     * Validate selection from a list of allowed values (for drop-down inputs, radio lists, etc.)
     *
     * @param Field|HasOptions $field
     * @param string[]|null    $values list of allowed values (or NULL to obtain allowed values from Field)
     * @param string           $error  error message
     *
     * @return $this
     *
     * @see HasOptions::getOptions()
     */
    public function selected(Field $field, array $values = null, $error = null)
    {
        if ($values === null) {
            if ($field instanceof HasOptions) {
                $values = array_map('strval', array_keys($field->getOptions()));
            } else {
                throw new RuntimeException("no allowed values provided");
            }
        }

        if (! in_array($this->getInput($field), $values, true)) {
            $this->error($field, $error ?: $this->lang[__FUNCTION__]);
        }

        return $this;
    }

    /**
     * Validate date/time input in the format specified by the given DateTimeField.
     *
     * @param DateTimeField $field
     * @param string        $error error message
     *
     * @return $this
     */
    public function datetime(DateTimeField $field, $error = null)
    {
        $input = $this->getInput($field);

        $time = @date_create_from_format($field->format, $input, $field->timezone);

        if (!$time || $time->format($field->format) != $input) {
            $this->error($field, $error ?: $this->lang[__FUNCTION__]);
        }

        return $this;
    }

    /**
     * Validate a cross-site request forgery (CSRF) token
     *
     * @param TokenField $field
     * @param string    $error  error message
     *
     * @return $this
     */
    public function token(TokenField $field, $error = null)
    {
        $input = $this->getInput($field);

        if (! $field->checkToken($input)) {
            $this->error($field, $error ?: $this->lang[__FUNCTION__], array('time' => $field->valid_from));
        }

        return $this;
    }
}
