<?php

use mindplay\kissform\CheckboxField;
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

class SampleDescriptor
{
    const OPTION_ONE = 'one';
    const OPTION_TWO = 'two';

    /** @var TokenField */
    public $token;

    /** @var SelectField */
    public $enum;

    /** @var DateTimeField */
    public $datetime;

    /** @var TextField */
    public $text;

    /** @var TextArea */
    public $textarea;

    /** @var PasswordField */
    public $password;

    /** @var HiddenField */
    public $hidden;

    /** @var EmailField */
    public $email;

    /** @var IntField */
    public $int;

    /** @var CheckboxField */
    public $bool;

    public function __construct()
    {
        $this->token = new TokenField('token', 'abc123');

        $this->enum = new SelectField('enum', array(
            self::OPTION_ONE => 'Option One',
            self::OPTION_TWO => 'Option Two',
        ));

        $this->datetime = new DateTimeField('datetime');

        $this->text = new TextField('text');

        $this->textarea = new TextArea('textarea');

        $this->password = new PasswordField('password');

        $this->hidden = new HiddenField('hidden');

        $this->email = new EmailField('email');

        $this->int = new IntField('int');

        $this->bool = new CheckboxField('bool');
    }
}

class ValidationDescriptor
{
    const CAUSE_PROGRAMMERS = 'p';
    const CAUSE_ARTISTS = 'a';

    /** @var TextField */
    public $email;

    /** @var TextField */
    public $confirm_email;

    /** @var IntField */
    public $donation;

    /** @var TextField */
    public $password;

    /** @var CheckboxField */
    public $agree;

    /** @var SelectField */
    public $cause;

    public function __construct()
    {
        $this->email = new TextField('email');

        $this->confirm_email = new TextField('confirm_email');

        $this->donation = new IntField('donation');
        $this->donation->min_value = 100;
        $this->donation->max_value = 1000;

        $this->password = new TextField('password');

        $this->agree = new CheckboxField('agree');

        $this->cause = new SelectField('cause', array(
            self::CAUSE_PROGRAMMERS => 'Starving Programmers',
            self::CAUSE_ARTISTS => 'Starving Artists',
        ));
    }
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
    'handles name, id and class-attributes',
    function () {
        $type = new SampleDescriptor();
        $form = new InputRenderer();

        eq($form->input($type->text, array('class' => array('foo', 'bar'))), '<input class="form-control foo bar" name="text" type="text"/>', 'folds multi-valued class attribute');
        eq($form->input($type->text, array('readonly' => true)), '<input class="form-control" name="text" readonly type="text"/>', 'handles boolean TRUE attribute value');
        eq($form->input($type->text, array('readonly' => false)), '<input class="form-control" name="text" type="text"/>', 'handles boolean FALSE attribute value');
        eq($form->input($type->text, array('foo' => null)), '<input class="form-control" name="text" type="text"/>', 'filters NULL-value attributes');

        $form->xhtml = true;
        eq($form->input($type->text, array('readonly' => true)), '<input class="form-control" name="text" readonly="readonly" type="text"/>', 'renders value-less attributes as valid XHTML');
        $form->xhtml = false;

        eq(invoke($form, 'createName', array($type->text)), 'text', 'name without prefix');
        eq(invoke($form, 'createId', array($type->text)), null, 'no id attribute when $id_prefix is NULL');

        $form->name_prefix = 'form';
        $form->id_prefix = 'form';

        eq(invoke($form, 'createName', array($type->text)), 'form[text]', 'name with prefix');
        eq(invoke($form, 'createId', array($type->text)), 'form-text', 'id with defined prefix');
    }
);

test(
    'builds input groups',
    function () {
        $type = new SampleDescriptor();
        $form = new InputRenderer();

        eq($form->group($type->text) . $form->endGroup(), '<div class="form-group"></div>');

        $form->model->errors['text'] = 'some error';

        eq($form->group($type->text), '<div class="form-group has-error">');

        $type->text->required = true;

        eq($form->group($type->text), '<div class="form-group is-required has-error">');

        eq($form->group($type->text, array('class' => 'foo')), '<div class="form-group is-required has-error foo">', 'merge with one class');

        eq($form->group($type->text, array('class' => array('foo', 'bar'))), '<div class="form-group is-required has-error foo bar">', 'merge with multiple classes');
    }
);

test(
    'builds various text input tags',
    function () {
        $type = new SampleDescriptor();
        $form = new InputRenderer();

        eq($form->input($type->text), '<input class="form-control" name="text" type="text"/>', 'basic input with no value-attribute');

        $form->model->input['text'] = 'Hello World';

        eq($form->input($type->text), '<input class="form-control" name="text" type="text" value="Hello World"/>', 'basic input with value-attribute');

        $type->text->max_length = 50;

        eq($form->input($type->text), '<input class="form-control" maxlength="50" name="text" type="text" value="Hello World"/>', 'input with value and maxlength-attributes');

        $type->text->placeholder = 'hello';

        eq($form->input($type->text), '<input class="form-control" maxlength="50" name="text" placeholder="hello" type="text" value="Hello World"/>', 'input with value, maxlength and placeholder-attributes');
        eq($form->input($type->text, array('data-foo' => 'bar')), '<input class="form-control" data-foo="bar" maxlength="50" name="text" placeholder="hello" type="text" value="Hello World"/>', 'input with custom data-attribute overridden');
        eq($form->input($type->text, array('placeholder' => 'override')), '<input class="form-control" maxlength="50" name="text" placeholder="override" type="text" value="Hello World"/>', 'input with placeholder-attribute overridden');

        $form->model->input['text'] = 'this & that';

        eq($form->input($type->text), '<input class="form-control" maxlength="50" name="text" placeholder="hello" type="text" value="this &amp; that"/>', 'input with value-attribute escaped as HTML');

        $form->model->input['password'] = 'supersecret';

        eq($form->input($type->password), '<input class="form-control" name="password" type="password" value="supersecret"/>', 'input with type=password');

        $form->model->input['hidden'] = 'this & that';

        eq($form->input($type->hidden), '<input name="hidden" type="hidden" value="this &amp; that"/>', 'hidden input (no class, placeholder or maxlength, etc.)');

        $form->model->input['email'] = 'foo@bar.baz';

        eq($form->input($type->email), '<input class="form-control" name="email" type="email" value="foo@bar.baz"/>', 'input with type=email (html5)');

        $form->model->input['textarea'] = 'this & that';

        eq($form->input($type->textarea), '<textarea class="form-control" name="textarea">this &amp; that</textarea>', 'simple textarea with content');

        ok(preg_match('#<input name="token" type="hidden" value="\w+"/>#', $form->input($type->token)) === 1, 'hidden input with CSRF token');
    }
);

test(
    'builds label tags',
    function () {
        $type = new SampleDescriptor();
        $form = new InputRenderer();

        expect(
            'RuntimeException',
            'because no id-attribute can be created',
            function () use ($form, $type) {
                $form->label($type->text);
            }
        );

        $form = new InputRenderer(null, 'form');

        eq($form->label($type->text), '', 'returns an empty string for unlabeled input');

        $type->text->label = 'Name:';

        eq($form->label($type->text), '<label class="control-label" for="form-text">Name:</label>');
    }
);

test(
    'builds labeled checkboxes',
    function () {
        $type = new SampleDescriptor();
        $form = new InputRenderer(null, 'form');

        $type->bool->label = 'I agree';

        eq($form->input($type->bool), '<div class="checkbox"><label><input name="form[bool]" type="checkbox" value="1"/>I agree</label></div>');
    }
);

test(
    'builds select/option tags',
    function () {
        $type = new SampleDescriptor();
        $form = new InputRenderer();

        $type->enum->required = false;

        eq($form->input($type->enum), '<select name="enum"><option value="one">Option One</option><option value="two">Option Two</option></select>');
    }
);

test(
    'builds date/time text inputs',
    function () {
        $type = new SampleDescriptor();
        $form = new InputRenderer();

        $form->model->input['datetime'] = '1975-07-07';

        eq($form->input($type->datetime), '<input class="form-control" data-ui="datetimepicker" name="datetime" readonly="readonly" type="text" value="1975-07-07"/>');
    }
);

test(
    'builds label/input groups',
    function () {
        $form = new InputRenderer(null, 'form');

        $field = new TextField('hello');

        $form->model->input['hello'] = 'World';

        eq($form->inputGroup($field), '<div class="form-group"><input class="form-control" id="form-hello" name="form[hello]" type="text" value="World"/></div>');
    }
);

test(
    'validator behavior',
    function () {
        $validator = new InputValidator(array());

        $type = new ValidationDescriptor();

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

        $validator->error($type->email, 'some {token}', array('token' => 'error'));

        eq($validator->valid, false, 'errors are present (is not valid)');
        eq($validator->invalid, true, 'errors are present (is invalid)');

        eq($validator->model->errors['email'], 'some error', 'error messages get formatted');

        $validator->error($type->email, 'some other error');

        eq($validator->model->errors['email'], 'some error', 'first error message is retained');

        $validator->model->clearError($type->email);

        eq($validator->valid, true, 'error message cleared');

        $validator->error($type->email, 'some error again');

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
                $v->numeric($f);
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
        $field = new DateTimeField('value');
        $field->setTimeZone('UTC');
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
        eq($field->getValue($model), $value, "{$type}::getValue() converts string to {$data}");
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
