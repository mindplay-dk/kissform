<?php

namespace mindplay\kissform\Test;

use mindplay\kissform\Fields\CheckboxField;
use mindplay\kissform\Fields\DateTimeField;
use mindplay\kissform\Fields\IntField;
use mindplay\kissform\Fields\SelectField;
use mindplay\kissform\Fields\TextField;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputValidation;
use mindplay\kissform\Validators\CheckLength;
use mindplay\kissform\Validators\CheckMaxLength;
use mindplay\kissform\Validators\CheckMaxValue;
use mindplay\kissform\Validators\CheckMinLength;
use mindplay\kissform\Validators\CheckMinValue;
use mindplay\kissform\Validators\CheckRange;
use mindplay\kissform\Validators\CheckAccept;
use mindplay\kissform\Validators\CheckParser;
use mindplay\kissform\Validators\CheckEmail;
use mindplay\kissform\Validators\CheckSameValue;
use mindplay\kissform\Validators\CheckInt;
use mindplay\kissform\Validators\CheckNumeric;
use mindplay\kissform\Validators\CheckRequired;
use mindplay\kissform\Validators\CheckSelected;
use mindplay\kissform\Validators\CheckPattern;
use mindplay\lang;
use UnitTester;

class ValidationCest
{
    public function validateRequired(UnitTester $I)
    {
        $field = new TextField('value');

        $I->testValidator(
            $field,
            new CheckRequired(),
            ['a', 'bbb'],
            ['', null]
        );
    }

    public function validateEmail(UnitTester $I)
    {
        $I->testValidator(
            new TextField('value'),
            new CheckEmail(),
            ['a@b.com', 'foo@bar.dk', '', null],
            ['123', 'foo@', '@foo.com']
        );
    }

    public function validateRange(UnitTester $I)
    {
        $field = new IntField('value');

        $I->testValidator(
            $field,
            new CheckRange(100, 1000),
            ['100', '500', '1000', '', null],
            ['99', '1001', '-1']
        );
    }

    public function validateMinValue(UnitTester $I)
    {
        $int_field = new IntField('value');

        $I->testValidator(
            $int_field,
            new CheckMinValue(100),
            ['100', '1000', '', null],
            ['-1', '0']
        );
    }

    public function validateMaxValue(UnitTester $I)
    {
        $int_field = new IntField('value');

        $I->testValidator(
            $int_field,
            new CheckMaxValue(1000),
            ['-1', '0', '1000', '', null],
            ['1001']
        );
    }

    public function validateInt(UnitTester $I)
    {
        $I->testValidator(
            new IntField('value'),
            new CheckInt(),
            ['0', '-1', '1', '123', '', null],
            ['-', 'foo', '0.0', '1.0', '123.4']
        );
    }

    public function validateNumericInput(UnitTester $I)
    {
        $I->testValidator(
            new IntField('value'),
            new CheckNumeric(),
            ['0', '-1', '1', '123', '0.0', '-1.0', '-1.1', '123.4', '123.1', '', null],
            ['-', 'foo']
        );
    }

    public function validateConfirmation(UnitTester $I)
    {
        $primary = new TextField('primary');
        $secondary = new TextField('secondary');

        $model = InputModel::create(['primary' => 'foo', 'secondary' => 'foo']);

        $validator = new InputValidation($model);
        $validator->validate($secondary, new CheckSameValue($primary));
        $I->assertTrue(! $model->hasError($primary), 'primary field has no error');
        $I->assertTrue(! $model->hasError($secondary), 'secondary field has no error');

        $model = InputModel::create(['primary' => 'foo', 'secondary' => 'bar']);

        $validator = new InputValidation($model);
        $validator->validate($secondary, new CheckSameValue($primary));
        $I->assertTrue(! $model->hasError($primary), 'primary field has no error');
        $I->assertTrue($model->hasError($secondary), 'secondary field has error');
    }

    public function validateInputLength(UnitTester $I)
    {
        $field = new TextField('value');

        $I->testValidator(
            $field,
            new CheckLength(5, 10),
            ['12345', '1234567890', '', null],
            ['1234', '12345678901']
        );
    }

    public function validateMinLength(UnitTester $I)
    {
        $field = new TextField('value');

        $I->testValidator(
            $field,
            new CheckMinLength(5),
            ['12345', '1234567890', '', null],
            ['1234']
        );
    }

    public function validateMaxLength(UnitTester $I)
    {
        $field = new TextField('value');

        $I->testValidator(
            $field,
            new CheckMaxLength(10),
            ['12345', '1234567890', '', null],
            ['12345678901']
        );
    }

    public function validateCheckbox(UnitTester $I)
    {
        $field = new CheckboxField('value');

        $I->testValidator(
            $field,
            new CheckAccept($field->checked_value),
            [$field->checked_value, true],
            ['', '0', null, 'true']
        );
    }

    public function validateSelection(UnitTester $I)
    {
        $field = new SelectField('value', ['foo' => 1, 'bar' => 2]);

        $I->testValidator(
            $field,
            new CheckSelected([1, 2]),
            ['1', '2', '', null],
            ['0', '3']
        );
    }

    public function validateDateTime(UnitTester $I)
    {
        $field = new DateTimeField('value', 'UTC', 'Y-m-d');

        $validator = new CheckParser($field, "error message");
        
        $I->testValidator(
            $field,
            $validator,
            ['1975-07-07', '2014-01-01', '2014-12-31', '', null],
            ['2014-1-1', '2014', '2014-13-01', '2014-12-32', '2014-0-1', '2014-1-0']
        );

        $field->format = 'j/n/Y';

        $I->testValidator(
            $field,
            $validator,
            ['7/7/1975', '1/1/2014', '31/12/2014', '', null],
            ['2014/01/01', '2014', '1/13/2014', '32/12/2014', '0/1/2014', '1/0/2014']
        );
    }

    public function validateMatch(UnitTester $I)
    {
        $field = new TextField('value');

        $I->testValidator(
            $field,
            new CheckPattern('/[a-z]+\d+/', 'whoops'),
            ['a1', 'abc123', '', null],
            ['123abc', '_']
        );
    }

    public function overrideLabel(UnitTester $I)
    {
        $field = new IntField("test");

        $model = InputModel::create(["test" => "not_a_number"]);

        $validator = new InputValidation($model);
        
        $validator->setLabel($field, "Blub");
        
        $validator->check($field);
        
        $I->assertSame($model->getError($field), lang::text("mindplay/kissform", "int", ["field" => "Blub"]));
    }
}
