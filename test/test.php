<?php

use mindplay\kissform\BoolField;
use mindplay\kissform\EnumField;
use mindplay\kissform\FormHelper;
use mindplay\kissform\IntField;
use mindplay\kissform\TextField;
use mindplay\kissform\FormValidator;

require __DIR__ . '/header.php';

header('Content-type: text/plain');

if (coverage()) {
    $filter = coverage()->filter();

    $filter->addDirectoryToWhitelist(dirname(__DIR__) . '/src');

    coverage()->setProcessUncoveredFilesFromWhitelist(true);

    coverage()->start('test');
}

class SampleDescriptor
{
    const OPTION_ONE = 'one';
    const OPTION_TWO = 'two';

    /** @var EnumField */
    public $enum;

    /** @var TextField */
    public $text;

    /** @var IntField */
    public $int;

    /** @var BoolField */
    public $bool;

    public function __construct()
    {
        $this->enum = new EnumField('enum');

        $this->enum->options = array(
            self::OPTION_ONE => 'Option One',
            self::OPTION_TWO => 'Option Two',
        );

        $this->text = new TextField('text');

        $this->int = new IntField('int');

        $this->bool = new BoolField('bool');
    }
}

class ValidationDescriptor
{
    /** @var TextField */
    public $email;

    /** @var TextField */
    public $confirm_email;

    /** @var IntField */
    public $donation;

    /** @var TextField */
    public $password;

    /** @var BoolField */
    public $agree;

    public function __construct()
    {
        $this->email = new TextField('email');

        $this->confirm_email = new TextField('confirm_email');

        $this->donation = new IntField('donation');

        $this->password = new TextField('password');

        $this->agree = new BoolField('agree');
    }
}

test(
    'handles name, id and class-attributes',
    function () {
        $type = new SampleDescriptor();
        $form = new FormHelper(array());

        eq($form->text($type->text, array('class' => array('foo', 'bar'))), '<input class="form-control foo bar" name="text" type="text"/>', 'merges multi-value class attribute');

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
        $form = new FormHelper(array());

        eq($form->group($type->text), '<div class="form-group">');

        $form->errors['text'] = 'some error';

        eq($form->group($type->text), '<div class="form-group has-error">');

        $type->text->required = true;

        eq($form->group($type->text), '<div class="form-group is-required has-error">');

        eq($form->group($type->text, 'div', array('class' => 'foo')), '<div class="form-group is-required has-error foo">', 'merge with one class');

        eq($form->group($type->text, 'span', array('class' => array('foo', 'bar'))), '<span class="form-group is-required has-error foo bar">', 'merge with multiple classes');
    }
);

test(
    'builds various text input tags',
    function () {
        $type = new SampleDescriptor();
        $form = new FormHelper(array());

        eq($form->text($type->text), '<input class="form-control" name="text" type="text"/>', 'basic input with no value-attribute');

        $form->state['text'] = 'Hello World';

        eq($form->text($type->text), '<input class="form-control" name="text" type="text" value="Hello World"/>', 'basic input with value-attribute');

        $type->text->max_length = 50;

        eq($form->text($type->text), '<input class="form-control" maxlength="50" name="text" type="text" value="Hello World"/>', 'input with value and maxlength-attributes');

        $type->text->placeholder = 'hello';

        eq($form->text($type->text), '<input class="form-control" maxlength="50" name="text" placeholder="hello" type="text" value="Hello World"/>', 'input with value, maxlength and placeholder-attributes');
        eq($form->text($type->text, array('data-foo' => 'bar')), '<input class="form-control" data-foo="bar" maxlength="50" name="text" placeholder="hello" type="text" value="Hello World"/>', 'input with custom data-attribute overridden');
        eq($form->text($type->text, array('placeholder' => 'override')), '<input class="form-control" maxlength="50" name="text" placeholder="override" type="text" value="Hello World"/>', 'input with placeholder-attribute overridden');

        $form->state['text'] = 'this & that';

        eq($form->text($type->text), '<input class="form-control" maxlength="50" name="text" placeholder="hello" type="text" value="this &amp; that"/>', 'input with value-attribute escaped as HTML');

        eq($form->password($type->text), '<input class="form-control" maxlength="50" name="text" placeholder="hello" type="password" value="this &amp; that"/>', 'input with type=password');

        eq($form->hidden($type->text), '<input class="form-control" name="text" placeholder="hello" type="hidden" value="this &amp; that"/>', 'hidden input (ignores $max_length)');

        eq($form->email($type->text), '<input class="form-control" maxlength="50" name="text" placeholder="hello" type="email" value="this &amp; that"/>', 'input with type=email (html5)');

        eq($form->textarea($type->text), '<textarea class="form-control" name="text" placeholder="hello">this &amp; that</textarea>', 'simple textarea with content');
    }
);

test(
    'builds label tags',
    function () {
        $type = new SampleDescriptor();
        $form = new FormHelper(array());

        expect(
            'RuntimeException',
            'because no id-attribute can be created',
            function () use ($form, $type) {
                $form->label($type->text);
            }
        );

        $form = new FormHelper(array(), 'form');

        eq($form->label($type->text), '', 'returns an empty string for unlabeled input');

        $type->text->label = 'Name:';

        eq($form->label($type->text), '<label class="control-label" for="form-text">Name:</label>');
    }
);

test(
    'builds labeled checkboxes',
    function () {
        $type = new SampleDescriptor();
        $form = new FormHelper(array(), 'form');

        $type->bool->label = 'I agree';

        eq($form->checkbox($type->bool), '<div class="checkbox"><label><input name="form[bool]" type="checkbox" value="1"/>I agree</label></div>');
    }
);

test(
    'builds select/option tags',
    function () {
        $type = new SampleDescriptor();
        $form = new FormHelper(array());

        $type->enum->required = false;

        eq($form->select($type->enum), '<select name="enum"><option value="one">Option One</option><option value="two">Option Two</option></select>');
    }
);

test(
    'builds date/time text inputs',
    function () {
        $type = new SampleDescriptor();
        $form = new FormHelper(array());

        $form->state['text'] = '1975-07-07';

        eq($form->date($type->text), '<input class="form-control" data-ui="datepicker" name="text" readonly="readonly" type="text" value="1975-07-07"/>');

        $form->state['text'] = '1975-07-07 12:00:00';

        eq($form->datetime($type->text), '<input class="form-control" data-ui="datetimepicker" name="text" readonly="readonly" type="text" value="1975-07-07 12:00:00"/>');
    }
);

test(
    'validator behavior',
    function () {
        $validator = new FormValidator(array());

        $type = new ValidationDescriptor();

        expect(
            'RuntimeException',
            'undefined property access',
            function () use ($validator) {
                $validator->foo_bar;
            }
        );

        eq($validator->valid, true, 'no errors initially (is valid)');
        eq($validator->invalid, false, 'no errors initially (is not invalid)');

        $validator->error($type->email, '%s %s', 'some', 'error');

        eq($validator->valid, false, 'errors are present (is not valid)');
        eq($validator->invalid, true, 'errors are present (is invalid)');

        eq($validator->errors['email'], 'some error', 'error messages get formatted');

        $validator->error($type->email, 'some other error');

        eq($validator->errors['email'], 'some error', 'first error message is retained');

        $validator->reset();

        eq($validator->valid, true, 'errors have been cleared');
    }
);

test(
    'can validate input',
    function () {
        $type = new ValidationDescriptor();

        $validator = new FormValidator(array(
            'email' => 'jdoe@email.com',
            'confirm_email' => 'jdoe@emaiiil.com', // bad e-mail
            'donation' => 'megabucks', // not a number
        ));

        $validator
            ->title($type->email, 'E-mail Address')
            ->required($type->email)
            ->email($type->email)
            ->confirm($type->email, $type->confirm_email)
            ->numeric($type->donation);

        ok(!isset($validator->errors['email']), 'email validation succeeds');
        ok(isset($validator->errors['confirm_email']), 'confirmation fails');
        ok(isset($validator->errors['donation']), 'donation is not a number');

        $validator->reset();

        $validator->state['email'] = 'botched_it!';
        $validator->state['donation'] = '100';

        $validator
            ->email($type->email)
            ->numeric($type->donation);

        ok(isset($validator->errors['email']), 'email validation fails');
        ok(!isset($validator->errors['donation']), 'numeric validation succeeds');

        // length and password validations, against an array:

        $validator->state['password'] = '';
        $validator->required($type->password);
        ok(isset($validator->errors['password']), 'input is required');

        $validator->clear($type->password);
        $validator->state['password'] = 'aaaaaa';
        $validator->required($type->password);
        ok(!isset($validator->errors['password']), 'passes required validation');

        $validator->clear($type->password);
        $validator->length($type->password, 1, 6);
        ok(!isset($validator->errors['password']), 'length in range');

        $validator->length($type->password, 8, 20);
        ok(isset($validator->errors['password']), 'length out of range');

        $validator->clear($type->password);
        ok(!isset($validator->errors['password']), 'error cleared');

        $validator->password($type->password);
        ok(isset($validator->errors['password']), 'invalid password');

        $validator->clear($type->password);
        $validator->state['password'] = 'aA1';
        $validator->password($type->password);
        ok(!isset($validator->errors['pasword']), 'valid password');

        $validator->checked($type->agree);
        ok(isset($validator->errors['agree']), 'not checked');
        $validator->clear($type->agree);
        $validator->state['agree'] = $type->agree->checked_value;
        $validator->checked($type->agree);
        ok(!isset($validator->errors['agree']), 'checked');

        // TODO debug and test float validation; add FloatField
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
