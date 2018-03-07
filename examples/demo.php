<?php

use mindplay\kissform\Facets\TokenServiceInterface;
use mindplay\kissform\Fields\CheckboxField;
use mindplay\kissform\Fields\HiddenField;
use mindplay\kissform\Fields\IntField;
use mindplay\kissform\Fields\TextField;
use mindplay\kissform\Fields\TokenField;
use mindplay\kissform\Framework\SessionTokenStore;
use mindplay\kissform\Framework\TokenService;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\InputValidation;
use mindplay\kissform\Validators\CheckToken;

require dirname(__DIR__) . '/vendor/autoload.php';

session_start();

class DonationForm
{
    /** @var HiddenField */
    public $token;
    
    /** @var TextField */
    public $first_name;

    /** @var TextField */
    public $last_name;

    /** @var IntField */
    public $amount;

    /** @var CheckboxField */
    public $i_agree;

    public function __construct(TokenServiceInterface $token_service)
    {
        $this->token = new TokenField(self::class, $token_service);
        
        $this->first_name = new TextField('first_name');
        $this->first_name->setLabel('First Name');
        $this->first_name->setRequired();

        $this->last_name = new TextField('last_name');
        $this->last_name->setLabel('Last Name');
        $this->last_name->setRequired();

        $this->amount = new IntField('amount');
        $this->amount->setRequired();
        $this->amount->setLabel('Donation Amount');
        $this->amount->min_value = 20;
        $this->amount->max_value = 1000;
        $this->amount->setPlaceholder('$20 up to $1,000');
        
        $this->i_agree = new CheckboxField('i_agree');
        $this->i_agree->setLabel('I Agree to Donate');
        $this->i_agree->setRequired();
    }
}

$token_service = new TokenService(new SessionTokenStore(), 'SuPeR_sEcReT_KeY');

$t = new DonationForm($token_service);

$model = InputModel::create(@$_SESSION[__FILE__]);

if (isset($_POST['form'])) {
    $model = InputModel::create($_POST['form']);

    $_SESSION[__FILE__] = $model;

    $validator = new InputValidation($model);

    $validator->check([
        $t->token,
        $t->first_name,
        $t->last_name,
        $t->amount,
        $t->i_agree,
    ]);
    
    if ($model->isValid()) {
        $message = 'Thank You for your Donation!';

        unset($_SESSION[__FILE__]);
    } else {
        header('Location: ' . $_SERVER['REQUEST_URI']);

        exit;
    }

    unset($validator);
}

$form = new InputRenderer($model, 'form');

$form->error_summary_class = "alert alert-danger"; // this example uses a basic bootstrap alert for the error-summary

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title>Donation Form</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <link rel="stylesheet" type="text/css" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.1/css/bootstrap.min.css"/>
    <style>
        /* note that bootstrap's checkbox/radio markup prevents styling - see: https://github.com/twbs/bootstrap/issues/19931 */
        .radio input[type=radio], .radio-inline input[type=radio], .checkbox input[type=checkbox], .checkbox-inline input[type=checkbox] {
            margin-left: 0;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>Make a Donation</h1>

    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?= $message ?></div>
        <p>&raquo; <a href="<?= basename(__FILE__) ?>">Donate Again!</a></p>
    <?php else: ?>
        <form method="post">
            <?= $form->render($t->token) ?>
            <?= $form->renderGroup($t->first_name) ?>
            <?= $form->renderGroup($t->last_name) ?>
            <?= $form->renderGroup($t->amount) ?>
            <?= $form->render($t->i_agree) ?>
            <input class="btn btn-lg btn-primary" type="submit" value="Donate" />
        </form>
    <?php endif ?>

    <?= $form->errorSummary() ?>

</div>

</body>

</html>
