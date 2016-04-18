<?php

namespace mindplay\kissform\Test;

use mindplay\kissform\Field;
use mindplay\kissform\Fields\CheckboxField;
use mindplay\kissform\Fields\DateSelectField;
use mindplay\kissform\Fields\DateTimeField;
use mindplay\kissform\Fields\EmailField;
use mindplay\kissform\Fields\HiddenField;
use mindplay\kissform\Fields\IntField;
use mindplay\kissform\Fields\PasswordField;
use mindplay\kissform\Fields\RadioGroup;
use mindplay\kissform\Fields\SelectField;
use mindplay\kissform\Fields\TextArea;
use mindplay\kissform\Fields\TextField;
use mindplay\kissform\Validators\CheckAccept;
use mindplay\kissform\Validators\CheckDateTime;
use mindplay\kissform\Validators\CheckEmail;
use mindplay\kissform\Validators\CheckInt;
use mindplay\kissform\Validators\CheckLength;
use mindplay\kissform\Validators\CheckMaxLength;
use mindplay\kissform\Validators\CheckMaxValue;
use mindplay\kissform\Validators\CheckMinLength;
use mindplay\kissform\Validators\CheckMinValue;
use mindplay\kissform\Validators\CheckRange;
use mindplay\kissform\Validators\CheckRequired;
use mindplay\kissform\Validators\CheckSelected;
use UnitTester;

class FieldConstraintCest
{
    public function createConstraintsForTextField(UnitTester $I)
    {
        $field = new TextField("value");

        $this->checkTextFieldConstraints($I, $field);
    }

    public function createConstraintsForPasswordField(UnitTester $I)
    {
        $field = new PasswordField("value");

        $this->checkTextFieldConstraints($I, $field);
    }

    public function createConstraintsForHiddenField(UnitTester $I)
    {
        $field = new HiddenField("value");

        $this->checkTextFieldConstraints($I, $field);
    }

    public function createConstraintsForTextArea(UnitTester $I)
    {
        $field = new TextArea("value");

        $this->checkTextFieldConstraints($I, $field);
    }

    public function createConstraintsForIntField(UnitTester $I)
    {
        $field = new IntField("value");

        $I->expectFieldConstraints($field, [
            CheckInt::class => [],
        ]);

        $field->setRequired(true);

        $I->expectFieldConstraints($field, [
            CheckRequired::class => [],
            CheckInt::class      => [],
        ]);

        $field->min_length = 10;

        $I->expectFieldConstraints($field, [
            CheckRequired::class  => [],
            CheckMinLength::class => ['min' => 10],
            CheckInt::class       => [],
        ]);

        $field->max_length = 20;

        $I->expectFieldConstraints($field, [
            CheckRequired::class => [],
            CheckLength::class   => ['min' => 10, 'max' => 20],
            CheckInt::class      => [],
        ]);

        $field->min_value = 1;

        $I->expectFieldConstraints($field, [
            CheckRequired::class => [],
            CheckLength::class   => ['min' => 10, 'max' => 20],
            CheckMinValue::class => ['min' => 1],
        ]);

        $field->max_value = 99;

        $I->expectFieldConstraints($field, [
            CheckRequired::class => [],
            CheckLength::class   => ['min' => 10, 'max' => 20],
            CheckRange::class    => ['min' => 1, 'max' => 99],
        ]);

        $field->min_value = null;

        $I->expectFieldConstraints($field, [
            CheckRequired::class => [],
            CheckLength::class   => ['min' => 10, 'max' => 20],
            CheckMaxValue::class => ['max' => 99],
        ]);

        $field->max_value = null;

        $field->min_length = null;

        $I->expectFieldConstraints($field, [
            CheckRequired::class  => [],
            CheckMaxLength::class => ['max' => 20],
            CheckInt::class       => [],
        ]);
    }

    public function createConstraintsForEmailField(UnitTester $I)
    {
        $field = new EmailField("value");

        $I->expectFieldConstraints($field, [
            CheckEmail::class => [],
        ]);

        $field->setRequired(true);

        $I->expectFieldConstraints($field, [
            CheckRequired::class => [],
            CheckEmail::class    => [],
        ]);

        $field->min_length = 10;

        $I->expectFieldConstraints($field, [
            CheckRequired::class  => [],
            CheckMinLength::class => ['min' => 10],
            CheckEmail::class     => [],
        ]);

        $field->max_length = 20;

        $I->expectFieldConstraints($field, [
            CheckRequired::class => [],
            CheckLength::class   => ['min' => 10, 'max' => 20],
            CheckEmail::class    => [],
        ]);

        $field->min_length = null;

        $I->expectFieldConstraints($field, [
            CheckRequired::class  => [],
            CheckMaxLength::class => ['max' => 20],
            CheckEmail::class     => [],
        ]);
    }

    public function createConstraintsForCheckbox(UnitTester $I)
    {
        $field = new CheckboxField("value");

        $I->expectFieldConstraints($field, [
            CheckAccept::class => ['checked_value' => $field->checked_value],
        ]);

        $field->setRequired(true);

        $I->expectFieldConstraints($field, [
            CheckAccept::class => ['checked_value' => $field->checked_value],
        ]);
    }

    public function createConstraintsForSelectTag(UnitTester $I)
    {
        $options = [1 => "foo", 2 => "bar"];

        $field = new SelectField("value", $options);

        $this->checkOptionFieldConstraints($I, $field, $options);
    }

    public function createConstraintsForRadioGroup(UnitTester $I)
    {
        $options = [1 => "foo", 2 => "bar"];

        $field = new RadioGroup("value", $options);

        $this->checkOptionFieldConstraints($I, $field, $options);
    }

    public function createConstraintsForDateTimeStringField(UnitTester $I)
    {
        $field = new DateTimeField("value", "Europe/Copenhagen", "Y-m-d H:i:s");

        $this->checkDateFieldConstraints($I, $field);
    }

    public function createConstraintsForDateSelectField(UnitTester $I)
    {
        $field = new DateSelectField("value");

        $this->checkDateFieldConstraints($I, $field);
    }

    public function createConstraintsForSelectField(UnitTester $I)
    {
        $field = new SelectField("value", ["m" => "Male", "f" => "Female"]);

        $I->expectFieldConstraints($field, [
            CheckSelected::class => ["options" => ["m", "f"]]
        ]);
    }

    private function checkOptionFieldConstraints(UnitTester $I, Field $field, array $options)
    {
        $field->setRequired(false);

        $I->expectFieldConstraints($field, [
            CheckSelected::class => ['options' => array_keys($options)]
        ]);

        $field->setRequired(true);

        $I->expectFieldConstraints($field, [
            CheckSelected::class => ['options' => array_keys($options)]
        ]);
    }

    private function checkTextFieldConstraints(UnitTester $I, TextField $field)
    {
        $field->setRequired(false);

        $I->expectFieldConstraints($field, []);

        $field->min_length = 3;

        $I->expectFieldConstraints($field, [
            CheckMinLength::class => ['min' => 3]
        ]);

        $field->max_length = 5;

        $I->expectFieldConstraints($field, [
            CheckLength::class => ['min' => 3, 'max' => 5]
        ]);

        $field->setRequired(true);

        $I->expectFieldConstraints($field, [
            CheckRequired::class => [],
            CheckLength::class   => ['min' => 3, 'max' => 5]
        ]);

        $field->min_length = null;

        $I->expectFieldConstraints($field, [
            CheckRequired::class  => [],
            CheckMaxLength::class => ['max' => 5]
        ]);
    }

    private function checkDateFieldConstraints(UnitTester $I, Field $field)
    {
        $field->setRequired(false);

        $I->expectFieldConstraints($field, [
            CheckDateTime::class => ['parser' => $field]
        ]);

        $field->setRequired(true);

        $I->expectFieldConstraints($field, [
            CheckRequired::class => [],
            CheckDateTime::class => ['parser' => $field]
        ]);
    }
}
