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

For example:

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
$form = new FormHelper($state, 'user');

$u = new UserForm();

?>
<form method="post">
    <?= $form->label($t->first_name) . $form->text($t->first_name) . '<br/>' ?>
    <?= $form->label($t->last_name) . $form->text($t->last_name) . '<br/>' ?>
    <input class="btn btn-lg btn-primary" type="submit" value="Save" />
</form>
```

Reuse the form model to validate user input:

```PHP
$validator = new FormValidator($_POST['user']);

$validator
    ->required($t->first_name)
    ->required($t->last_name);

if ($validator->valid) {
    // no errors!
} else {
    var_dump($validator->errors); // returns e.g. array("first_name" => "First Name is required")
}
```

Note that only one error is recorded per field - the first one encountered.

That's really basic - of course it does other expected useful things, like:

 * Comes preconfigured with Bootstrap class-names as defaults, because, why not.

 * Adds `class="has-error"` to inputs that have an error message.

 * Adds `class="is-required"` to inputs that are required.

 * Creates `name` and `id` attributes, according to really simple rules, e.g.
   prefix/suffix, no ugly magic or weird conventions to learn.

 * Field titles get reused, e.g. between `<label>` tags and error messages, but
   you can also customize displayed names in error messages, if needed.

If you think that sounds very simple, that's because it is - this library does
very little and gets out of your way whenever you need to do something fancy.

There is very little to learn, and nothing needs to fit into a "box" - there
is no "architecture" here, just two simple classes, and yes, a little more work
in some cases, but nothing you can't handle. Now get back to work.

You can/should extend the form renderer with your application-specific input
types, and more importantly, extend that into model/case-specific renderers -
it's just one class, so apply your OOP skills for fun and profit!

Why input validation, as opposed to (business) model validation?

 * Because business validation is usually specific to a scenario - you might as
   well do it with simple if-statements in a controller or service, and then
   manually add errors to the validator.

 * Because input validation is simpler - it's just one class, and you can/should
   extend the class with case-specific validations, since you're often going to
   have validations that pertain to only one scenario/model/case.

 * There may be simple scenarios in which a business model isn't necessary.

 * Because I said so.

Because you're working with raw query strings/arrays (e.g. `$_POST` or `$_GET`)
implementing the post/redirect/get pattern is dead simple, as shown in this
[basic example](https://github.com/mindplay-dk/kissform/blob/master/test/example.php).

Oh, you think your huge, complicated framework of validators and model binders
and type converters and what-have-you is more cool/advanced/easy/dope/fun?

I beg to differ.

Shut up and love it.

You're welcome.
