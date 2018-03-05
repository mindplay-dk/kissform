mindplay/kissform
=================

Model-driven form rendering and input validation.

[![PHP Version](https://img.shields.io/badge/php-5.5%2B-blue.svg)](https://packagist.org/packages/mindplay/kissform)
[![Build Status](https://travis-ci.org/mindplay-dk/kissform.svg?branch=master)](https://travis-ci.org/mindplay-dk/kissform)
[![Code Coverage](https://scrutinizer-ci.com/g/mindplay-dk/kissform/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/kissform/?branch=master)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/mindplay-dk/kissform/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/mindplay-dk/kissform/?branch=master)

Model-driven means it's driven by models - that means, step one is building
a model that describes details of the rendered inputs on the form, and how
the input gets validated.

Model-driven in this library does *not* mean "baked into your domain model",
it means building a dedicated model describing aspects of form input/output.


## Concepts

The library consists of the following types, with the following responsibilities:

  * **`Field`** classes describe the possible input elements on a form - what
    they are, what they look like, and how they behave; *not* their state.

  * **`InputModel`** contains the state of the form - the values in the input
    elements and any error-messages. This is a thin wrapper around raw `$_GET`
    or `$_POST` data, combined with error state - it can be serialized, which
    means you can safely store it in a session variable.

  * **`InputRenderer`** renders HTML elements (basic inputs, labels, etc.) and/or
    delegates more complex rendering to `Field` instances - it provides fields with
    an `InputModel` instance (form values and errors) at the time of rendering.

  * **`InputValidation`** manages the validation process by running validators against
    fields.

  * **`ValidatorInterface`** defines the interface of validator types, which implement
    validation logic for e.g. e-mail addresses, numbers, date/time, etc.

Most `Field` types are capable of producing some built-in validators - these can be created and
checked by calling `InputValidation::check()`. For example, setting the `$min_length` property of
a `TextField` will cause it to create a `CheckMinLength` validator.

This design is based on the idea that there are no overlapping concerns between
form rendering and input validation - one is about output, the other is about input.

Assuming you use [PRG](http://en.wikipedia.org/wiki/Post/Redirect/Get),
when the form is rendered initially, there is no user input, thus nothing to validate;
if the form fails validation, the validation occurs during a POST request, and the
actual form rendering occurs during a separate GET request. In other words, form
rendering and validation never actually occur during the same request.


## Usage

A basic form model might look like this:

```PHP
class UserForm
{
    /** @var TextField */
    public $first_name;

    /** @var TextField */
    public $last_name;

    public function __construct()
    {
        $this->first_name = new TextField('first_name');
        $this->first_name->setLabel('First Name');
        $this->first_name->setRequired();

        $this->last_name = new TextField('last_name');
        $this->last_name->setLabel('Last Name');
        $this->last_name->setRequired();
    }
}
```

Use the model to render form inputs:

```PHP
$form = new InputRenderer(@$_POST['user'], 'user');

$t = new UserForm();

?>
<form method="post">
    <?= $form->labelFor($t->first_name) . $form->render($t->first_name) . '<br/>' ?>
    <?= $form->labelFor($t->last_name) . $form->render($t->last_name) . '<br/>' ?>
    <input class="btn btn-lg btn-primary" type="submit" value="Save" />
</form>
```

Reuse the form model to validate user input:

```PHP
$model = InputModel::create($_POST['user']);

$validator = new InputValidation($model);

$validator->check([$t->first_name, $t->last_name]);

if ($model->isValid()) {
    // no errors!
} else {
    var_dump($model->errors); // returns e.g. array("first_name" => "First Name is required")
}
```

Note that only one error is recorded per field - the first one encountered.

Once the input has passed validation, you can extract values from the individual fields:

```php
$first_name = $form->first_name->getValue($model);
$last_name = $form->last_name->getValue($model);
```

To implement editing of existing data with a form, you can also inject state into the form model:

```php
$form->first_name->setValue($model, "Rasmus");
$form->last_name->setValue($model, "Schultz");
```

Note that the `getValue()` and `setValue()` methods of every `Field` type are type-aware - for
example, the `IntField` returns `int`, `CheckboxField` returns `bool`, and so on.

Only valid values of the appropriate types can be exchanged with Fields in this manner - if you
need access to possiby-invalid, raw input-values, use the `getInput()` and `setInput()` methods
of `InputModel` instead.

This demonstrates the most basic patterns - please see the [demo](examples/demo.php) for a working
example of the post/redirect/get cycle and CSRF protection.


## Other Features

This library has other expected and useful features, including:

 * Comes preconfigured with Bootstrap class-names as defaults, because, why not.

 * Adds `class="has-error"` to inputs that have an error message.

 * Adds `class="is-required"` to inputs that are required.

 * Creates `name` and `id` attributes, according to really simple rules, e.g.
   prefix/suffix, no name mangling or complicated conventions to learn.

 * Field titles get reused, e.g. between `<label>` tags and error messages, but
   you can also customize displayed names in error messages, if needed.

 * Default error messages can be localized/customized.

 * A basic error-summary can be generated with `InputRenderer::errorSummary()`.

It deliberately does not implement any of the following:

 * Trivial elements: things like `<form>`, `<fieldset>` and `<legend>` - you don't
   need code to help you create these simple tags, just type them out; your templates
   will be easier to read and maintain.

 * Form layout: there are too many possible variations, and it's just HTML, which
   is really easy to do in the first place - it's not worthwhile.

 * A plugin architecture: you don't need one - just use everyday OO patterns to
   solve problems like a thrifty programmer. Extend the renderer and validator
   as needed for your business/project/module/scenario/model, etc.

This library is a tool, not a silver bullet - it does as little as possible, avoids
inventing complex concepts that can describe every detail, and instead deals primarily
with the repetitive/error-prone stuff, and gets out of your way when you need it to.

There is very little to learn, and nothing needs to fit into a "box" - there
is little "architecture" here, no "plugins" or "extensions", mostly just simple OOP.

You can/should extend the form renderer with your application-specific input
types, and more importantly, extend that into model/case-specific renderers -
it's just one class, so apply your OOP skills for fun and profit!

### Why input validation, as opposed to (domain) model validation?

 * Because domain validation is usually specific to a scenario - you might as
   well do it with simple if-statements in a controller or service, and then
   manually add errors to the validator.

 * Because input validation is simpler - it's just one class, and you can/should
   extend the class with case-specific validations, since you're often going to
   have validations that pertain to only one scenario/model/case. Bulding reusable
   domain validation rules as components would be a lot more complicated - many
   of these would be applicable to only on scenario/case and would never actually
   get reused, so they don't even benefit from this complexity.

 * There are simple scenarios in which a domain model isn't even useful, such
   as contact or comment forms, etc.
