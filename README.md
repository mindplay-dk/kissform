mindplay/kissform
=================

Model-driven form rendering and input validation.

Yeah, I know, "another form library", but this is probably a bit different
from what you're used to, so give it a chance.

Model-driven means it's driven by models - that means, step one is building
a model that describes details of the rendered inputs on the form, and how
the input gets validated.

Model-driven in this library does *not* mean "baked into your business model",
it means building a dedicated model describing aspects of form input/output.


## Concepts

The library consists of four key classes with the following responsibilities:

  * **`Field`** classes describe the possible input elements on a form - what
    they are, what they look like, and how they behave; *not* their state.
    They also convert native (domain) values to/from form state.
  
  * **`InputModel`** contains the state of the form - the values in the input
    elements and any error-messages. This is a thin wrapper around raw `$_GET`
    or `$_POST` data - it can be serialized, which means you can stick it in
    a session variable directly.
  
  * **`InputRenderer`** renders form elements (and labels, etc.) using information
    from `Field` instances, and state (values, errors) from an InputModel instance.
    
  * **`InputValidator`** validates input using information from `Field` instances and
    state (values) from an `InputModel` - it adds any new validation errors to the
    `InputModel` while performing validations.

Note that `InputRenderer` delegates to `Field` to render the actual input element via
the `RenderableField` interface - in other words, every `Field` has a built-in default
"template" for rendering itself. (This makes sense, because parsing/generating input
state/values is mutually dependent on the precise HTML input(s) being used.)

This design is based on the idea that there are no overlapping concerns between
form rendering and input validation - one is about output, the other is about input,
it merely so happens that the same information can be used to configure the components
that handle these concerns.

Assuming you use [PRG](http://en.wikipedia.org/wiki/Post/Redirect/Get),
when the form is rendered initially, there is no user input, thus nothing to validate;
if the form fails validation, the validation occurs during the POST request, and the
actual form rendering occurs during the second GET request. In other words, form
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
        $this->first_name->label = 'First Name';
        $this->first_name->required = true;

        $this->last_name = new TextField('last_name');
        $this->last_name->label = 'Last Name';
        $this->last_name->required = true;
    }
}
```

Use the model to render form inputs:

```PHP
$form = new InputRenderer(@$_POST['user'], 'user');

$t = new UserForm();

?>
<form method="post">
    <?= $form->label($t->first_name) . $form->text($t->first_name) . '<br/>' ?>
    <?= $form->label($t->last_name) . $form->text($t->last_name) . '<br/>' ?>
    <input class="btn btn-lg btn-primary" type="submit" value="Save" />
</form>
```

Reuse the form model to validate user input:

```PHP
$validator = new InputValidator($_POST['user']);

$validator
    ->required($t->first_name)
    ->required($t->last_name);

if ($validator->valid) {
    // no errors!
} else {
    var_dump($validator->model->errors); // returns e.g. array("first_name" => "First Name is required")
}
```

Note that only one error is recorded per field - the first one encountered.


## Other Features

This library has other expected and useful features, including:

 * Comes preconfigured with Bootstrap class-names as defaults, because, why not.

 * Adds `class="has-error"` to inputs that have an error message.

 * Adds `class="is-required"` to inputs that are required.

 * Creates `name` and `id` attributes, according to really simple rules, e.g.
   prefix/suffix, no ugly magic or weird conventions to learn.

 * Field titles get reused, e.g. between `<label>` tags and error messages, but
   you can also customize displayed names in error messages, if needed.
   
 * Default error messages can be localized/customized.

It deliberately does not implement any of the following:

 * Trivial elements: things like `<form>`, `<fieldset>` and `<legend>` - you don't
   need code to help you create these simple tags, just type them out; your templates
   will be easier to read and maintain.

 * Form layout: there are too many possible variations, and it's just HTML, which
   is really easy to do in the first place - it's not worthwhile.
   
 * Language selection: again, too many scenarios - you could be checking browser
   headers, domain-names or a user-defined setting, that's your business; using
   dependency injection, you should have no trouble injecting the required set
   of language constants in a multi-language scenario.
   
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

#### Why input validation, as opposed to (business) model validation?

 * Because business validation is usually specific to a scenario - you might as
   well do it with simple if-statements in a controller or service, and then
   manually add errors to the validator.

 * Because input validation is simpler - it's just one class, and you can/should
   extend the class with case-specific validations, since you're often going to
   have validations that pertain to only one scenario/model/case. Bulding reusable
   business validation rules as components would be a lot more complicated - many
   of these would be applicable to only on scenario/case and would never actually
   get reused, so they don't even benefit from this complexity.

 * There are simple scenarios in which a business model isn't even useful, such
   as contact or comment forms, etc.

Because you're working with raw query strings/arrays (e.g. `$_POST` or `$_GET`)
implementing the post/redirect/get pattern is dead simple, as shown in this
[basic example](https://github.com/mindplay-dk/kissform/blob/master/test/example.php).


## Contributions

Yes, please - PSR-2 and 4, update and run the test-suite, pull request, thank you!
