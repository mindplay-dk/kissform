<?php

namespace Helper;

use Exception;
use InvalidArgumentException;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;

class Unit extends \Codeception\Module
{
    /**
     * @param string          $type
     * @param string|callable $message_or_function
     * @param callable|null   $function
     */
    public function assertException($type, $message_or_function, callable $function = null)
    {
        if (func_num_args() === 3) {
            $message = $message_or_function;
        } else { // 2 args
            $message = null;
            $function = $message_or_function;
        }

        $exception = null;

        try {
            call_user_func($function);
        } catch (Exception $e) {
            $exception = $e;
        }

        $exception_type = $exception ? get_class($exception) : 'null';

        \PHPUnit_Framework_Assert::assertThat(
            $exception,
            new \PHPUnit_Framework_Constraint_Exception($type),
            $exception_type . " NOT EQUAL TO "  . $type
        );

        if ($message !== null) {
            \PHPUnit_Framework_Assert::assertThat($exception,
                new \PHPUnit_Framework_Constraint_ExceptionMessage($message));
        }
    }

    /**
     * Test a Validator against known valid and invalid values.
     *
     * @param \mindplay\kissform\Field                     $field     Field instance where $name === 'value'
     * @param \mindplay\kissform\Facets\ValidatorInterface $validator function (FormValidator $v, Field $f): void; should invoke the validation function
     * @param string[]                               $valid     list of valid values
     * @param string[]                               $invalid   list of invalid values
     */
    public function testValidator(
        \mindplay\kissform\Field $field,
        \mindplay\kissform\Facets\ValidatorInterface $validator,
        array $valid,
        array $invalid
    )
    {
        $field->setLabel('Value');

        foreach ($valid as $valid_value) {
            $model = InputModel::create(['value' => $valid_value]);

            $validation = new InputValidation($model);

            $validation->validate($field, $validator);

            $this->assertFalse($model->hasError($field),
                "field " . get_class($field) . " accepts value: " . $this->format($valid_value));
        }

        foreach ($invalid as $invalid_value) {
            $model = InputModel::create(['value' => $invalid_value]);

            $validation = new InputValidation($model);

            $validation->validate($field, $validator);

            $this->assertTrue($model->hasError($field),
                "field " . get_class($field) . " rejects value: " . $this->format($invalid_value) . " (" . @$model->getError($field) . ")");
        }
    }

    /**
     * Test the built-in Constraint Validators (provided by a Field) against known valid and invalid values.
     *
     * @param \mindplay\kissform\Field $field
     * @param string[]           $valid
     * @param string[]           $invalid
     */
    public function testConstraints(\mindplay\kissform\Field $field, array $valid, array $invalid)
    {
        $field->setLabel('Value');

        foreach ($valid as $valid_value) {
            $model = InputModel::create(['value' => $valid_value]);

            $validation = new InputValidation($model);

            $validation->check($field);

            $info = $model->hasError($field)
                ? " rejected by: " . $this->format($this->findValidatorRejecting($field, $valid_value))
                : '';

            $this->assertFalse($model->hasError($field),
                "field " . get_class($field) . " accepts value: " . $this->format($valid_value) . $info);
        }

        foreach ($invalid as $invalid_value) {
            $model = InputModel::create(['value' => $invalid_value]);

            $validation = new InputValidation($model);

            $validation->check($field);

            $info = $model->hasError($field)
                ? ''
                : ", validators applied: " . $this->format($field->createValidators(), true);

            $this->assertTrue(
                $model->hasError($field),
                "field " . get_class($field) . " rejects value: " . $this->format($invalid_value)
                . ", with message: " . @$model->getError($field) . $info
            );
        }
    }

    /**
     * @param \mindplay\kissform\Facets\FieldInterface $field
     * @param array                              $expected map where class-name => property-name => value
     */
    public function expectFieldConstraints(\mindplay\kissform\Facets\FieldInterface $field, array $expected)
    {
        $validators = $field->createValidators();

        $validator_names = array_map('get_class', $validators);

        $this->assertSame($validator_names, array_keys($expected),
            "expected constraint validators: " . implode(", ", array_keys($expected)));

        $index = 0;

        foreach ($expected as $expected_class => $expected_properties) {
            $validator = $validators[$index++];

            $actual_properties = [];

            foreach ($expected_properties as $expected_property => $expected_value) {
                $prop = new \ReflectionProperty($validator, $expected_property);

                $prop->setAccessible(true);

                $actual_properties[$expected_property] = $prop->getValue($validator);
            }

            $this->assertSame($expected_properties, $actual_properties,
                "expected properties of " . get_class($validator) . ": " . $this->format($expected_properties, true)
                . ", got: " . $this->format($actual_properties, true));
        }
    }

    /**
     * @param \mindplay\kissform\Facets\FieldInterface $field
     * @param mixed                              $value
     *
     * @return \mindplay\kissform\Facets\ValidatorInterface|null
     */
    private function findValidatorRejecting(\mindplay\kissform\Facets\FieldInterface $field, $value)
    {
        $model = InputModel::create(['value' => $value]);

        $validators = $field->createValidators();

        $validation = new InputValidation($model);

        foreach ($validators as $validator) {
            $validator->validate($field, $model, $validation);

            if ($model->hasError($field)) {
                return $validator;
            }
        }

        return null;
    }

    /**
     * @param \mindplay\kissform\Field $field
     * @param mixed[]            $conversions map where input string => converted value
     * @param mixed[]            $invalid     list of unacceptable values
     */
    public function testConversion(\mindplay\kissform\Field $field, $conversions, $invalid)
    {
        $type = get_class($field);

        $this->assertSame($field->getName(), 'value', 'pre-condition: Field must be named "value"');

        $model = InputModel::create();

        foreach ($conversions as $input => $value) {
            $input = (string) $input;

            $data = '(' . gettype($value) . ') ' . $this->format($value);

            $field->setValue($model, $value);
            $this->assertSame($model->input['value'], $input, "{$type}::setValue() converts {$data} to string");

            $model->input['value'] = $input;
            $this->assertSame($field->getValue($model), $value,
                "{$type}::getValue() converts string \"{$input}\" to {$data}");
        }

        $field->setValue($model, null);

        $this->assertSame($field->getValue($model), null, "{$type} handles NULL input");

        foreach ($invalid as $value) {
            $this->assertException(
                InvalidArgumentException::class,
                null,
                function () use ($field, $model, $value) {
                    $field->setValue($model, $value);
                }
            );
        }
    }

    /**
     * Test for partial substrings which must appear (in order) in a given string
     *
     * @param string   $string
     * @param string[] $expected
     */
    public function expectParts($string, array $expected)
    {
        $last_offset = 0;
        $all_ok = true;

        foreach ($expected as $part) {
            $offset = strpos($string, $part, $last_offset);

            $ok = ($offset !== false) && ($offset >= $last_offset);

            $all_ok = $all_ok && $ok;

            $this->assertTrue($ok, 'contains part: ' . $part);

            $last_offset = $offset;
        }

        $this->assertTrue($all_ok, "contains all expected parts in order ({$string})");
    }

    /**
     * @param mixed $value
     * @param bool  $verbose
     *
     * @return string
     */
    public function format($value, $verbose = false)
    {
        if ($value instanceof Exception) {
            return get_class($value)
            . ($verbose
                ? ": \"" . $value->getMessage() . "\"\n\n" . $value->getTraceAsString() :
                ": \"" . $value->getMessage() . "\"");
        }

        if (! $verbose && is_array($value)) {
            return 'array[' . count($value) . ']';
        }

        if (is_bool($value)) {
            return $value ? 'TRUE' : 'FALSE';
        }

        if (is_object($value) && ! $verbose) {
            return get_class($value);
        }

        if (is_string($value)) {
            return "\"" . addcslashes($value, "\"") . "\"";
        }

        return print_r($value, true);
    }
}
