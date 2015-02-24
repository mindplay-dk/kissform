<?php

use mindplay\kissform\BoolField;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\InputValidator;
use mindplay\kissform\IntField;
use mindplay\kissform\TextField;

require __DIR__ . '/header.php';

session_start();

class DonationForm
{
    /** @var TextField */
    public $first_name;

    /** @var TextField */
    public $last_name;

    /** @var IntField */
    public $amount;

    /** @var BoolField */
    public $i_agree;

    public function __construct()
    {
        $this->first_name = new TextField('first_name');
        $this->first_name->label = 'First Name';
        $this->first_name->required = true;

        $this->last_name = new TextField('last_name');
        $this->last_name->label = 'Last Name';
        $this->last_name->required = true;

        $this->amount = new IntField('amount');
        $this->amount->label = 'Donation Amount';
        $this->amount->min_value = 20;
        $this->amount->max_value = 1000;
        $this->amount->placeholder = '$20 up to $1,000';

        $this->i_agree = new BoolField('i_agree');
        $this->i_agree->label = 'I Agree to Donate';
        $this->i_agree->required = true;
    }
}

$t = new DonationForm();

$model = InputModel::create(@$_SESSION[__FILE__]);

unset($_SESSION[__FILE__]);

if (isset($_POST['form'])) {
    $model->input = $_POST['form'];

    $_SESSION[__FILE__] = $model;

    $validator = new InputValidator($model);

    $validator
        ->required($t->first_name)
        ->required($t->last_name)
        ->required($t->amount)
        ->int($t->amount)
        ->checked($t->i_agree);

    if ($validator->invalid) {
        header('Location: ' . $_SERVER['REQUEST_URI']);

        exit;
    } else {
        $message = 'Thank You for your Donation!';
    }

    unset($validator);
}

$form = new InputRenderer($model, 'form');

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Donation Form</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css"/>
</head>

<body>

<div class="container">

<h1>Make a Donation</h1>

<form method="post">
    <?= $form->group($t->first_name) . $form->label($t->first_name) . $form->text($t->first_name) . $form->endGroup() ?>
    <?= $form->group($t->last_name) . $form->label($t->last_name) . $form->text($t->last_name) . $form->endGroup() ?>
    <?= $form->group($t->amount) . $form->label($t->amount) . $form->text($t->amount) . $form->endGroup() ?>
    <?= $form->checkbox($t->i_agree) ?>
    <input class="btn btn-lg btn-primary" type="submit" value="Donate" />
</form>

<?php if (isset($message)): ?>
    <div class="alert alert-success"><?= $message ?></div>
<?php endif ?>

<?php if ($model->hasErrors()): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($model->errors as $error): ?>
            <li><?= $error ?></li>
            <?php endforeach ?>
        </ul>
    </div>
<?php endif ?>

</div>

</body>

</html>
