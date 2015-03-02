<?php

use mindplay\kissform\CheckboxField;
use mindplay\kissform\DateSelectField;
use mindplay\kissform\EmailField;
use mindplay\kissform\HiddenField;
use mindplay\kissform\InputModel;
use mindplay\kissform\PasswordField;
use mindplay\kissform\TextArea;
use mindplay\kissform\TokenField;
use mindplay\kissform\DateTimeField;
use mindplay\kissform\SelectField;
use mindplay\kissform\Field;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\IntField;
use mindplay\kissform\TextField;
use mindplay\kissform\InputValidator;

require __DIR__ . '/header.php';

header('Content-type: text/plain');

if (coverage()) {
    $filter = coverage()->filter();

    $filter->addDirectoryToWhitelist(dirname(__DIR__) . '/src');

    coverage()->start('test');
}

/**
 * Test a validation function against known valid and invalid values.
 *
 * @param Field    $field    Field instance where $name === 'value'
 * @param Closure  $function function (FormValidator $v, Field $f): void; should invoke the validation function
 * @param string[] $valid    list of valid values
 * @param string[] $invalid  list of invalid values
 */
function testValidator(Field $field, $function, array $valid, array $invalid)
{
    $field->label = 'Value';

    foreach ($valid as $valid_value) {
        $validator = new InputValidator(array('value' => $valid_value));

        call_user_func($function, $validator, $field);

        ok(!isset($validator->model->errors['value']), "field " . get_class($field) . " accepts value: " . format($valid_value));
    }

    foreach ($invalid as $invalid_value) {
        $validator = new InputValidator(array('value' => $invalid_value));

        call_user_func($function, $validator, $field);

        ok(isset($validator->model->errors['value']), "field " . get_class($field) . " rejects value: " . format($invalid_value) . " (" . @$validator->model->errors['value'] . ")");
    }
}

test(
    'InputModel behavior',
    function () {
        $model = InputModel::create();
        eq($model->input, array(), 'defaults to empty input');
        eq($model->errors, array(), 'defaults to empty error list');

        $field = new TextField('test');
        $model->setInput($field, 'foo');
        eq($model->input, array('test' => 'foo'), 'can set input value');
        eq($model->getInput($field), 'foo', 'can get input value');

        $model->setInput($field, null);
        eq($model->input, array(), 'removes NULL values');

        eq($model->hasErrors(), false, 'model has no errors initially');
        eq($model->hasError($field), false, 'field has no error initially');
        $model->setError($field, 'bang');
        eq($model->errors, array('test' => 'bang'));
        eq($model->hasError($field), true, 'field has error');
        eq($model->hasErrors(), true, 'model has errors');

        $model->clearError($field);
        eq($model->hasErrors(), false, 'model has no errors after clearing');
        eq($model->hasError($field), false, 'field has no error after clearing');

        $model->setError($field, 'bang');
        $model->clearErrors();
        eq($model->hasErrors(), false, 'model has no errors after clearing all');
        eq($model->hasError($field), false, 'field has no error after clearing all');
    }
);

test(
    'handles name, id and class-attributes',
    function () {
        $form = new InputRenderer();
        $field = new TextField('text');

        eq($form->input($field, array('class' => array('foo', 'bar'))), '<input class="form-control foo bar" name="text" type="text"/>', 'folds multi-valued class attribute');
        eq($form->input($field, array('readonly' => true)), '<input class="form-control" name="text" readonly type="text"/>', 'handles boolean TRUE attribute value');
        eq($form->input($field, array('readonly' => false)), '<input class="form-control" name="text" type="text"/>', 'handles boolean FALSE attribute value');
        eq($form->input($field, array('foo' => null)), '<input class="form-control" name="text" type="text"/>', 'filters NULL-value attributes');

        $form->xhtml = true;
        eq($form->input($field, array('readonly' => true)), '<input class="form-control" name="text" readonly="readonly" type="text"/>', 'renders value-less attributes as valid XHTML');
        $form->xhtml = false;

        eq($form->createName($field), 'text', 'name without prefix');
        eq($form->createId($field), null, 'no id attribute when $id_prefix is NULL');

        $form->name_prefix = 'form';
        $form->id_prefix = 'form';

        eq($form->createName($field), 'form[text]', 'name with prefix');
        eq($form->createId($field), 'form-text', 'id with defined prefix');

        $form->name_prefix = array('form', 'subform');
        $form->id_prefix = 'form-subform';
        eq($form->createName($field), 'form[subform][text]', 'renderer name with double prefix');
        eq($form->createId($field), 'form-subform-text', 'id for renderer name with double prefix');
    }
);

test(
    'builds HTML tags and attributes',
    function () {
        $renderer = new InputRenderer();

        eq($renderer->tag('input', array('type' => 'text')), '<input type="text"/>', 'self-closing tag');

        eq($renderer->tag('div', array(), 'Foo &amp; Bar'), '<div>Foo &amp; Bar</div>', 'tag with inner HTML');

        eq($renderer->tag('script', array(), ''), '<script></script>', 'empty tag');

        eq($renderer->openTag('div'), '<div>', 'open tag');

        eq($renderer->attrs(array('a' => false, 'b' => null, 'c' => '', 'd' => array())), '', 'filters empty attributes');

        eq($renderer->attrs(array('a' => 'foo', 'b' => 'bar')), ' a="foo" b="bar"', 'adds a leading space');

    }
);

test(
    'merges attributes',
    function () {
        $renderer = new InputRenderer();

        eq($renderer->merge(array('a' => '1'), array('a' => '2')), array('a' => '2'));

        eq($renderer->merge(array('a' => '1'), array('b' => '2')), array('a' => '1', 'b' => '2'));

        eq($renderer->merge(array('a' => '1', 'class' => 'foo')), array('a' => '1', 'class' => 'foo'));

        eq($renderer->merge(array('class' => 'foo'), array('class' => 'bar')), array('class' => array('foo', 'bar')));
    }
);

test(
    'builds input groups',
    function () {
        $form = new InputRenderer();
        $field = new TextField('text');

        eq($form->group() . $form->endGroup(), '<div class="form-group"></div>');

        eq($form->group($field) . $form->endGroup(), '<div class="form-group"></div>');

        $form->model->errors['text'] = 'some error';

        eq($form->group($field), '<div class="form-group has-error">');

        $field->required = true;

        eq($form->group($field), '<div class="form-group required has-error">');

        eq($form->group($field, array('class' => 'foo')), '<div class="form-group foo required has-error">', 'merge with one class');

        eq($form->group($field, array('class' => array('foo', 'bar'))), '<div class="form-group foo bar required has-error">', 'merge with multiple classes');
    }
);

test(
    'TextField behavior',
    function () {
        $form = new InputRenderer();

        $field = new TextField('value');

        eq($form->input($field), '<input class="form-control" name="value" type="text"/>', 'basic input with no value-attribute');
    }
);

test(
    'TextField behavior',
    function () {
        $form = new InputRenderer();
        $model = $form->model;
        $field = new TextField('value');

        eq($form->input($field), '<input class="form-control" name="value" type="text"/>', 'basic input with no value-attribute');

        $field->setValue($model, 'Hello World');

        eq($form->input($field), '<input class="form-control" name="value" type="text" value="Hello World"/>', 'basic input with value-attribute');

        $field->max_length = 50;

        eq($form->input($field), '<input class="form-control" maxlength="50" name="value" type="text" value="Hello World"/>', 'input with value and maxlength-attribute');

        $field->placeholder = 'hello';

        eq($form->input($field), '<input class="form-control" maxlength="50" name="value" placeholder="hello" type="text" value="Hello World"/>', 'input with value, maxlength and placeholder-attributes');
        eq($form->input($field, array('data-foo' => 'bar')), '<input class="form-control" data-foo="bar" maxlength="50" name="value" placeholder="hello" type="text" value="Hello World"/>', 'input with custom data-attribute overridden');
        eq($form->input($field, array('placeholder' => 'override')), '<input class="form-control" maxlength="50" name="value" placeholder="override" type="text" value="Hello World"/>', 'input with placeholder-attribute overridden');

        $field->setValue($model, 'this & that');

        eq($form->input($field), '<input class="form-control" maxlength="50" name="value" placeholder="hello" type="text" value="this &amp; that"/>', 'input with value-attribute escaped as HTML');
    }
);

test(
    'TextField behavior',
    function () {
        $form = new InputRenderer();
        $model = $form->model;
        $field = new PasswordField('value');

        $field->setValue($model, 'supersecret');

        eq($form->input($field), '<input class="form-control" name="value" type="password" value="supersecret"/>', 'input with type=password');
    }
);

test(
    'HiddenField behavior',
    function () {
        $form = new InputRenderer();
        $model = $form->model;
        $field = new HiddenField('value');

        $field->setValue($model, 'this & that');

        eq($form->input($field), '<input name="value" type="hidden" value="this &amp; that"/>', 'hidden input (no class, placeholder or maxlength, etc.)');
    }
);

test(
    'EmailField behavior',
    function () {
        $form = new InputRenderer();
        $model = $form->model;
        $field = new EmailField('value');

        $field->setValue($model, 'foo@bar.baz');

        eq($form->input($field), '<input class="form-control" name="value" type="email" value="foo@bar.baz"/>', 'input with type=email (html5)');
    }
);

test(
    'TextArea behavior',
    function () {
        $form = new InputRenderer();
        $model = $form->model;
        $field = new TextArea('value');

        $field->setValue($model, 'this & that');

        eq($form->input($field), '<textarea class="form-control" name="value">this &amp; that</textarea>', 'simple textarea with content');
    }
);

test(
    'TokenField behavior',
    function () {
        $form = new InputRenderer();
        $field = new TokenField('token', 'abc123');

        ok(preg_match('#<input name="token" type="hidden" value="\w+"/>#', $form->input($field)) === 1, 'hidden input with CSRF token');
    }
);

test(
    'builds label tags',
    function () {
        $form = new InputRenderer();
        $field = new TextField('text');

        expect(
            'RuntimeException',
            'because no id-attribute can be created',
            function () use ($form, $field) {
                $form->label($field);
            }
        );

        $form = new InputRenderer(null, 'form');

        expect(
            'RuntimeException',
            'because no label can be created',
            function () use ($form, $field) {
                $form->label($field);
            }
        );

        $field->label = 'Name:';

        eq($form->label($field), '<label class="control-label" for="form-text">Name:</label>');
    }
);

test(
    'builds labeled checkboxes',
    function () {
        $form = new InputRenderer(null, 'form');
        $field = new CheckboxField('bool');

        eq($form->input($field), '<div class="checkbox"><input name="form[bool]" type="checkbox" value="1"/></div>');

        $field->label = 'I agree';

        eq($form->input($field), '<div class="checkbox"><label><input name="form[bool]" type="checkbox" value="1"/>I agree</label></div>');

        $field->wrapper_class = null;

        eq($form->input($field), '<label><input name="form[bool]" type="checkbox" value="1"/>I agree</label>');
    }
);

test(
    'builds select/option tags',
    function () {
        $form = new InputRenderer();

        $field = new SelectField('value', array(
            1 => 'Option One',
            2 => 'Option Two',
        ));

        eq($form->input($field), '<select name="value"><option value="1">Option One</option><option value="2">Option Two</option></select>');

        $field->setValue($form->model, 1);

        eq($form->input($field), '<select name="value"><option selected value="1">Option One</option><option value="2">Option Two</option></select>');
    }
);

test(
    'DateTimeField behavior',
    function () {
        $form = new InputRenderer();
        $field = new DateTimeField('value', 'Europe/Copenhagen');
        $field->setValue($form->model, 173919600);

        eq($form->input($field), '<input class="form-control" data-ui="datetimepicker" name="value" readonly type="text" value="1975-07-07 00:00:00"/>');
    }
);

test(
    'DateSelectField behavior',
    function () {
        $form = new InputRenderer();
        $field = new DateSelectField('value', 'Europe/Copenhagen');

        $field->setValue($form->model, 173919600);

        eq(
            $form->model->getInput($field),
            array(
                DateSelectField::KEY_YEAR  => '1975',
                DateSelectField::KEY_MONTH => '7',
                DateSelectField::KEY_DAY   => '7',
            ),
            'generates expected input from given value'
        );

        eq($field->getValue($form->model), 173919600, 'recreates date timestamp from input');

        $field->year_min = 1974;
        $field->year_max = 1976;

        # TODO eq($form->input($field), '');
    }
);

test(
    'builds label/input groups',
    function () {
        $form = new InputRenderer(null, 'form');

        $field = new TextField('hello');
        $field->label = 'Hello!';

        $form->model->input['hello'] = 'World';

        eq($form->inputGroup($field), '<div class="form-group"><label class="control-label" for="form-hello">Hello!</label><input class="form-control" id="form-hello" name="form[hello]" type="text" value="World"/></div>');
    }
);

test(
    'validator behavior',
    function () {
        $validator = new InputValidator(array());
        $field = new TextField('email');

        expect(
            'RuntimeException',
            'undefined property access',
            function () use ($validator) {
                /** @noinspection PhpUndefinedFieldInspection that's the whole point! */
                $validator->foo_bar;
            }
        );

        eq($validator->valid, true, 'no errors initially (is valid)');
        eq($validator->invalid, false, 'no errors initially (is not invalid)');

        $validator->error($field, 'some {token}', array('token' => 'error'));

        eq($validator->valid, false, 'errors are present (is not valid)');
        eq($validator->invalid, true, 'errors are present (is invalid)');

        eq($validator->model->errors['email'], 'some error', 'error messages get formatted');

        $validator->error($field, 'some other error');

        eq($validator->model->errors['email'], 'some error', 'first error message is retained');

        $validator->model->clearError($field);

        eq($validator->valid, true, 'error message cleared');

        $validator->error($field, 'some error again');

        $validator->reset();

        eq($validator->valid, true, 'errors have been cleared');
    }
);

test(
    'validate required()',
    function () {
        testValidator(
            new TextField('value'),
            function (InputValidator $v, Field $f) {
                $v->required($f);
            },
            array('a', 'bbb'),
            array('', null)
        );
    }
);

test(
    'validate email()',
    function () {
        testValidator(
            new TextField('value'),
            function (InputValidator $v, TextField $f) {
                $v->email($f);
            },
            array('a@b.com', 'foo@bar.dk'),
            array(123, '123', 'foo@', '@foo.com')
        );
    }
);

test(
    'validate range()',
    function () {
        $int_field = new IntField('value');
        $int_field->min_value = 100;
        $int_field->max_value = 1000;

        testValidator(
            $int_field,
            function (InputValidator $v, IntField $f) {
                $v->range($f);
            },
            array(100, 500, 1000),
            array(99, 1001, -1, null, '')
        );
    }
);

test(
    'validate minValue()',
    function () {
        $int_field = new IntField('value');
        $int_field->min_value = 100;

        testValidator(
            $int_field,
            function (InputValidator $v, IntField $f) {
                $v->minValue($f);
            },
            array(100, 1000),
            array(-1, 0, null, '')
        );
    }
);

test(
    'validate maxValue()',
    function () {
        $int_field = new IntField('value');
        $int_field->max_value = 1000;

        testValidator(
            $int_field,
            function (InputValidator $v, IntField $f) {
                $v->maxValue($f);
            },
            array(-1, 0, 1000),
            array(1001, null, '')
        );
    }
);

test(
    'validate int()',
    function () {
        testValidator(
            new IntField('value'),
            function (InputValidator $v, IntField $f) {
                $v->int($f);
            },
            array('0', '-1', '1', '123'),
            array('', null, '-', 'foo', '0.0', '1.0', '123.4')
        );
    }
);

test(
    'validate numeric()',
    function () {
        testValidator(
            new IntField('value'),
            function (InputValidator $v, IntField $f) {
                $v->float($f);
            },
            array('0', '-1', '1', '123', '0.0', '-1.0', '-1.1', '123.4', '123.1'),
            array('', null, '-', 'foo')
        );
    }
);

test(
    'validate confirm()',
    function () {
        $field = new TextField('value');
        $other = new TextField('other');

        $validator = new InputValidator(array('value' => 'foo', 'other' => 'foo'));
        $validator->confirm($field, $other);
        ok(!isset($validator->model->errors['value']), 'value field has no error');
        ok(!isset($validator->model->errors['other']), 'other field has no error');

        $validator = new InputValidator(array('value' => 'foo', 'other' => 'bar'));
        $validator->confirm($field, $other);
        ok(!isset($validator->model->errors['value']), 'value field has no error');
        ok(isset($validator->model->errors['other']), 'other field has error');
    }
);

test(
    'validate length()',
    function () {
        $field = new TextField('value');
        $field->min_length = 5;
        $field->max_length = 10;

        testValidator(
            $field,
            function (InputValidator $v, TextField $f) {
                $v->length($f);
            },
            array('12345','1234567890'),
            array('', null, '1234', '12345678901')
        );
    }
);

test(
    'validate minLength()',
    function () {
        $field = new TextField('value');
        $field->min_length = 5;

        testValidator(
            $field,
            function (InputValidator $v, TextField $f) {
                $v->minLength($f);
            },
            array('12345','1234567890'),
            array('', null, '1234')
        );
    }
);

test(
    'validate maxLength()',
    function () {
        $field = new TextField('value');
        $field->max_length = 10;

        testValidator(
            $field,
            function (InputValidator $v, TextField $f) {
                $v->length($f);
            },
            array('12345','1234567890','',null),
            array('12345678901')
        );
    }
);

test(
    'validate checked()',
    function () {
        $field = new CheckboxField('value');

        testValidator(
            $field,
            function (InputValidator $v, CheckboxField $f) {
                $v->checked($f);
            },
            array($field->checked_value, true),
            array('', '0', null, 'true')
        );
    }
);

test(
    'validate selected()',
    function () {
        $field = new SelectField('value', array(
            '1' => 'foo',
            '2' => 'bar',
        ));

        testValidator(
            $field,
            function (InputValidator $v, SelectField $f) {
                $v->selected($f);
            },
            array('1', '2', true),
            array('0', '3', null)
        );
    }
);

test(
    'validate password()',
    function () {
        $field = new TextField('value');

        testValidator(
            $field,
            function (InputValidator $v, TextField $f) {
                $v->password($f);
            },
            array('aA1', 'aA', 'a1', '1a'),
            array('111', 'aaa', 'AAA')
        );
    }
);

test(
    'validate datetime()',
    function () {
        $field = new DateTimeField('value', 'UTC');
        $field->format = 'Y-m-d';

        testValidator(
            $field,
            function (InputValidator $v, DateTimeField $f) {
                $v->datetime($f);
            },
            array('1975-07-07', '2014-01-01', '2014-12-31'),
            array('2014-1-1', '2014', '2014-13-01', '2014-12-32', '2014-0-1', '2014-1-0')
        );
    }
);

test(
    'validate token()',
    function () {
        $field = new TokenField('value', 'abc123');

        $not_valid_yet =  $field->createToken();

        testValidator(
            $field,
            function (InputValidator $v, TokenField $f) {
                $v->token($f);
            },
            array(),
            array($not_valid_yet)
        );

        $field->timestamp += $field->valid_from;
        $valid = $not_valid_yet;

        testValidator(
            $field,
            function (InputValidator $v, TokenField $f) {
                $v->token($f);
            },
            array($valid), // now it's valid!
            array('', null, '1' . $valid) // never valid
        );
    }
);

test(
    'TokenField token expiration',
    function () {
        $field = new TokenField('token', 'abc123');

        $token = $field->createToken();
        ok(strlen($token) > 0, 'it creates a token', base64_decode($token));

        ok($token !== $field->createToken(), 'it creates unique tokens');

        $token = $field->createToken();

        ok($field->checkToken($token) === false, 'token invalid when submitted too soon');

        $timestamp = $field->timestamp;

        $field->timestamp = $timestamp + $field->valid_from;

        ok($field->checkToken($token) === true, 'token valid when submitted before expiration');

        $field->timestamp = $timestamp + $field->valid_to;

        ok($field->checkToken($token) === true, 'token valid when submitted on time');

        $secret = $field->secret; // save correct secret
        $field->secret .= '1'; // wrongify
        ok($field->checkToken($token) === false, 'token invalid when using the wrong secret');
        $field->secret = $secret; // restore correct secret
        ok($field->checkToken('1' . $token) === false, 'token invalid after tampering');

        $field->timestamp = $timestamp + $field->valid_to + 1;

        ok($field->checkToken($token) === false, 'token invalid when submitted after expiration');
    }
);

/**
 * @param Field   $field
 * @param mixed[] $conversions map where input string => converted value
 * @param mixed[] $invalid     list of unacceptable values
 */
function testConversion(Field $field, $conversions, $invalid) {
    $type = get_class($field);

    $field->name = 'value';

    $model = InputModel::create();

    foreach ($conversions as $input => $value) {
        $input = (string) $input;

        $data = '(' . gettype($value) . ') ' . format($value);

        $field->setValue($model, $value);
        eq($model->input['value'], $input, "{$type}::setValue() converts {$data} to string");

        $model->input['value'] = $input;
        eq($field->getValue($model), $value, "{$type}::getValue() converts string \"{$input}\" to {$data}");
    }

    $field->setValue($model, null);
    eq($field->getValue($model), null, "{$type} handles NULL input");

    foreach ($invalid as $value) {
        $data = '(' . gettype($value) . ') ' . format($value);

        expect(
            'InvalidArgumentException',
            "{$type} rejects invalid value: {$data}",
            function () use ($field, $model, $value) {
                $field->setValue($model, $value);
            }
        );
    }
}

test(
    'IntField conversions',
    function () {
        testConversion(
            new IntField('value'),
            array(
                "12345" => 12345,
                "0" => 0,
            ),
            array("aaa", array(), 123.456)
        );
    }
);

test(
    'DateTimeField conversions',
    function () {
        testConversion(
            new DateTimeField('value', 'Europe/Copenhagen'),
            array(
                '1975-07-07 00:00:00' => 173919600
            ),
            array("aaa", array(), 123.456, "2014-01-01")
        );
    }
);

if (coverage()) {
    coverage()->stop();

    $report = new PHP_CodeCoverage_Report_Text(10, 90, false, false);
    echo $report->process(coverage(), false);

    $report = new PHP_CodeCoverage_Report_Clover();
    $report->process(coverage(), __DIR__ . '/build/logs/clover.xml');
}

exit(status());
