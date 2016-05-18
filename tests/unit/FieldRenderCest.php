<?php

namespace mindplay\kissform\Test;

use mindplay\kissform\Fields\CheckboxField;
use mindplay\kissform\Fields\DateSelectField;
use mindplay\kissform\Fields\DateTimeField;
use mindplay\kissform\Fields\EmailField;
use mindplay\kissform\Fields\FloatField;
use mindplay\kissform\Fields\HiddenField;
use mindplay\kissform\Fields\InlineRadioGroup;
use mindplay\kissform\Fields\IntField;
use mindplay\kissform\Fields\PasswordField;
use mindplay\kissform\Fields\RadioGroup;
use mindplay\kissform\Fields\SelectField;
use mindplay\kissform\Fields\TextArea;
use mindplay\kissform\Fields\TextField;
use mindplay\kissform\InputRenderer;
use UnitTester;

class FieldRenderCest
{
    public function renderTextFields(UnitTester $I)
    {
        $form = new InputRenderer();
        $model = $form->model;
        $field = new TextField('value');

        $I->assertSame('<input class="form-control" id="form-value" name="value" type="text"/>', $form->render($field),
            'basic input with no value-attribute');

        $field->setValue($model, 'Hello World');

        $I->assertSame('<input class="form-control" id="form-value" name="value" type="text" value="Hello World"/>',
            $form->render($field), 'basic input with value-attribute');

        $field->max_length = 50;

        $I->assertSame('<input class="form-control" id="form-value" maxlength="50" name="value" type="text" value="Hello World"/>',
            $form->render($field), 'input with value and maxlength-attribute');

        $field->setPlaceholder('hello');

        $I->assertSame('<input class="form-control" id="form-value" maxlength="50" name="value" placeholder="hello" type="text" value="Hello World"/>',
            $form->render($field), 'input with value, maxlength and placeholder-attributes');
        $I->assertSame('<input class="form-control" data-foo="bar" id="form-value" maxlength="50" name="value" placeholder="hello" type="text" value="Hello World"/>',
            $form->render($field, ['data-foo' => 'bar']), 'input with custom data-attribute overridden');
        $I->assertSame('<input class="form-control" id="form-value" maxlength="50" name="value" placeholder="override" type="text" value="Hello World"/>',
            $form->render($field, ['placeholder' => 'override']), 'input with placeholder-attribute overridden');

        $field->setValue($model, 'this & that');

        $I->assertSame('<input class="form-control" id="form-value" maxlength="50" name="value" placeholder="hello" type="text" value="this &amp; that"/>',
            $form->render($field), 'input with value-attribute escaped as HTML');

        $field = new TextField("value");

        $field->setPattern('[A-Z]{2}', 'Enter two-letter ISO language code');

        $I->assertSame('<input class="form-control" data-pattern-error="Enter two-letter ISO language code" id="form-value" name="value" pattern="[A-Z]{2}" type="text" value="this &amp; that"/>',
            $form->render($field));
    }

    public function renderPasswordField(UnitTester $I)
    {
        $form = new InputRenderer();
        $model = $form->model;
        $field = new PasswordField('value');

        $field->setValue($model, 'supersecret');

        $I->assertSame('<input class="form-control" id="form-value" name="value" type="password" value=""/>', $form->render($field),
            'input with type=password');
    }

    public function renderHiddenField(UnitTester $I)
    {
        $form = new InputRenderer();
        $model = $form->model;
        $field = new HiddenField('value');

        $field->setValue($model, 'this & that');

        $I->assertSame('<input name="value" type="hidden" value="this &amp; that"/>', $form->render($field),
            'hidden input (no class, placeholder or maxlength, etc.)');
    }

    public function renderEmailField(UnitTester $I)
    {
        $form = new InputRenderer();
        $model = $form->model;
        $field = new EmailField('value');

        $field->setValue($model, 'foo@bar.baz');

        $I->assertSame('<input class="form-control" id="form-value" name="value" type="email" value="foo@bar.baz"/>',
            $form->render($field), 'input with type=email (html5)');
    }

    public function renderTextArea(UnitTester $I)
    {
        $form = new InputRenderer();
        $model = $form->model;
        $field = new TextArea('value');

        $field->setValue($model, 'this & that');

        $I->assertSame('<textarea class="form-control" id="form-value" name="value">this &amp; that</textarea>', $form->render($field),
            'simple textarea with content');
    }

    public function renderCheckboxes(UnitTester $I)
    {
        $form = new InputRenderer(null, 'form');
        $field = new CheckboxField('bool');

        $I->assertSame('<div class="checkbox"><input id="form-bool" name="form[bool]" type="checkbox" value="1"/></div>',
            $form->render($field));

        $field->label = 'I agree';

        $I->assertSame('<div class="checkbox"><input id="form-bool" name="form[bool]" type="checkbox" value="1"/><label for="form-bool">I agree</label></div>',
            $form->render($field));

        $field->wrapper_class = null;

        $I->assertSame('<input id="form-bool" name="form[bool]" type="checkbox" value="1"/><label for="form-bool">I agree</label>',
            $form->render($field));

        $I->assertSame(false, $field->getValue($form->model), 'unchecked exposed as FALSE in the model');

        $field->setValue($form->model, true);

        $I->assertSame('<input checked id="form-bool" name="form[bool]" type="checkbox" value="1"/><label for="form-bool">I agree</label>',
            $form->render($field));

        $I->assertSame(true, $field->getValue($form->model), 'checked exposed as TRUE in the model');

        $form->setLabel($field, null);

        $I->assertSame(
            '<input checked id="form-bool" name="form[bool]" type="checkbox" value="1"/>',
            $form->render($field),
            'can suppress label tag'
        );
    }

    public function renderSelectTags(UnitTester $I)
    {
        $form = new InputRenderer();

        $field = new SelectField('value', [
            1 => 'Option One',
            2 => 'Option Two',
        ]);

        $I->assertSame('<select class="form-control" id="form-value" name="value"><option value="1">Option One</option><option value="2">Option Two</option></select>',
            $form->render($field));

        $field->setValue($form->model, 1);

        $I->assertSame('<select class="form-control" id="form-value" name="value"><option selected value="1">Option One</option><option value="2">Option Two</option></select>',
            $form->render($field));

        $field->disabled = 'Please select';

        $I->assertSame('<select class="form-control" id="form-value" name="value"><option disabled>Please select</option><option selected value="1">Option One</option><option value="2">Option Two</option></select>',
            $form->render($field));

        $field->setValue($form->model, null);

        $I->assertSame('<select class="form-control" id="form-value" name="value"><option disabled selected>Please select</option><option value="1">Option One</option><option value="2">Option Two</option></select>',
            $form->render($field));
    }

    public function renderRadioGroups(UnitTester $I)
    {
        $form = new InputRenderer(null, null, "form");

        $field = new RadioGroup('value', [
            '1' => 'Option One',
            '2' => 'Option Two',
        ]);

        $I->assertSame('<div class="radio"><input id="form-value-1" name="value" type="radio" value="1"/><label for="form-value-1">Option One</label></div><div class="radio"><input id="form-value-2" name="value" type="radio" value="2"/><label for="form-value-2">Option Two</label></div>',
            $form->render($field));

        $field->setValue($form->model, 1);

        $I->assertSame('<div class="radio"><input checked id="form-value-1" name="value" type="radio" value="1"/><label for="form-value-1">Option One</label></div><div class="radio"><input id="form-value-2" name="value" type="radio" value="2"/><label for="form-value-2">Option Two</label></div>',
            $form->render($field));

        // disabled wrapper tags:

        $field->wrapper_tag = null;

        $I->assertSame('<input checked id="form-value-1" name="value" type="radio" value="1"/><label for="form-value-1">Option One</label><input id="form-value-2" name="value" type="radio" value="2"/><label for="form-value-2">Option Two</label>',
            $form->render($field));
    }

    public function renderIntField(UnitTester $I)
    {
        $form = new InputRenderer();

        $field = new IntField("value");

        $I->assertSame('<input class="form-control" id="form-value" name="value" pattern="-?\d*" type="number"/>', $form->render($field));

        $field->min_value = -1;

        $I->assertSame('<input class="form-control" id="form-value" min="-1" name="value" pattern="-?\d*" type="number"/>', $form->render($field));

        $field->min_value = 1;
        $field->max_value = 99;

        $I->assertSame('<input class="form-control" id="form-value" max="99" min="1" name="value" pattern="\d*" type="number"/>', $form->render($field));
    }

    public function renderFloatField(UnitTester $I)
    {
        $form = new InputRenderer();

        $field = new FloatField("value");

        $I->assertSame('<input class="form-control" id="form-value" name="value" pattern="-?\d*(\.(?=\d))?\d*" type="number"/>', $form->render($field));

        $field->min_value = -1;

        $I->assertSame('<input class="form-control" id="form-value" min="-1" name="value" pattern="-?\d*(\.(?=\d))?\d*" type="number"/>', $form->render($field));

        $field->min_value = 1;
        $field->max_value = 99;

        $I->assertSame('<input class="form-control" id="form-value" max="99" min="1" name="value" pattern="\d*(\.(?=\d))?\d*" type="number"/>', $form->render($field));
    }

    public function renderDateTimeField(UnitTester $I)
    {
        $form = new InputRenderer();
        $field = new DateTimeField('value', 'Europe/Copenhagen', 'Y-m-d H:i:s', ['readonly' => true]);
        $field->setValue($form->model, 173919600);

        $I->assertSame('<input class="form-control" id="form-value" name="value" readonly type="text" value="1975-07-07 00:00:00"/>',
            $form->render($field));
    }

    public function renderDateSelector(UnitTester $I)
    {
        $form = new InputRenderer();
        $field = new DateSelectField('value', 'Europe/Copenhagen');

        $field->setValue($form->model, 173919600);

        $I->assertSame(
            [
                DateSelectField::KEY_YEAR  => '1975',
                DateSelectField::KEY_MONTH => '7',
                DateSelectField::KEY_DAY   => '7',
            ],
            $form->model->getInput($field), 'generates expected input from given value'
        );

        $I->assertSame(173919600, $field->getValue($form->model), 'recreates date timestamp from input');

        $field->year_min = 1974;
        $field->year_max = 1976;

        $field->setRequired(true);

        $I->expectParts(
            $form->render($field), [
                '<select class="form-control day" id="form-value-day" name="value[day]">',
                '<option value="1">1</option>',
                '<option selected value="7">7</option>',
                '<option value="31">31</option>',
                '</select>',
                '<select class="form-control month" id="form-value-month" name="value[month]">',
                '<option value="1">January</option>',
                '<option selected value="7">July</option>',
                '<option value="12">December</option>',
                '</select>',
                '<select class="form-control year" id="form-value-year" name="value[year]">',
                '<option value="1974">1974</option>',
                '<option selected value="1975">1975</option>',
                '<option value="1976">1976</option>',
                '</select>',
            ]
        );

        $field->setRequired(false);

        $I->expectParts(
            $form->render($field), [
                '<select class="form-control day" id="form-value-day" name="value[day]">',
                '<option disabled>Day</option>',
                '<select class="form-control month" id="form-value-month" name="value[month]">',
                '<option disabled>Month</option>',
                '<select class="form-control year" id="form-value-year" name="value[year]">',
                '<option disabled>Year</option>',
            ]
        );

        $field->setValue($form->model, null);

        $I->expectParts(
            $form->render($field), [
                '<select class="form-control day" id="form-value-day" name="value[day]">',
                '<option disabled selected>Day</option>',
                '<select class="form-control month" id="form-value-month" name="value[month]">',
                '<option disabled selected>Month</option>',
                '<select class="form-control year" id="form-value-year" name="value[year]">',
                '<option disabled selected>Year</option>',
            ]
        );
    }
}
