<?php

namespace mindplay\kissform\Test;

use mindplay\kissform\Fields\TextField;
use mindplay\kissform\InputModel;
use UnitTester;

class InputModelCest
{
    public function useInputModel(UnitTester $I)
    {
        $model = InputModel::create();

        $I->assertSame([], $model->input, 'defaults to empty input');
        $I->assertSame([], $model->getErrors(), 'defaults to empty error list');

        $field = new TextField('test');
        $model->setInput($field, 'foo');
        $I->assertSame(['test' => 'foo'], $model->input, 'can set input value');
        $I->assertSame('foo', $model->getInput($field), 'can get input value');

        $model->setInput($field, null);
        $I->assertSame([], $model->input, 'removes NULL values');

        $I->assertSame(false, $model->hasErrors(), 'model has no errors initially');
        $I->assertSame(false, $model->isValid(), 'model is not valid initially (because it has not been validated)');
        $I->assertSame(false, $model->hasError($field), 'field has no error initially');
        $model->setError($field, 'bang');
        $I->assertSame(['test' => 'bang'], $model->getErrors());
        $I->assertSame(true, $model->hasError($field), 'field has error');
        $I->assertSame(true, $model->hasErrors(), 'model has errors');
        $I->assertSame(false, $model->isValid(), 'model still is not valid');

        $model->clearError($field);
        $I->assertSame(false, $model->hasErrors(), 'model has no errors after clearing');
        $I->assertSame(false, $model->isValid(), 'model still is not valid');
        $I->assertSame(false, $model->hasError($field), 'field has no error after clearing');

        $model->setError($field, 'bang');
        $model->clearErrors();
        $I->assertSame(false, $model->hasErrors(), 'model has no errors after clearing all');
        $I->assertSame(false, $model->hasError($field), 'field has no error after clearing all');
        $I->assertSame(false, $model->isValid(), 'model is still not valid');

        $model->clearErrors(true);
        $I->assertSame(true, $model->isValid(),
            'model is valid after resetting with TRUE (e.g. when you create a validator)');
        $model->setError($field, 'bang');
        $I->assertSame(false, $model->isValid(), 'model is invalid after adding one error');
    }

    public function maintainErrorState(UnitTester $I)
    {
        $model = InputModel::create();

        $field = new TextField('email');

        $I->assertFalse($model->isValid(), 'model is not considered valid until validated, even if it has no errors');
        $I->assertFalse($model->hasErrors(), 'no errors initially (is not invalid)');

        $model->setError($field, 'first error');

        $I->assertFalse($model->isValid(), 'errors are present (is not valid)');
        $I->assertTrue($model->hasErrors(), 'errors are present (is invalid)');

        $I->assertSame('first error', $model->getError($field), 'can get error message');

        $model->setError($field, 'second error');

        $I->assertSame('first error', $model->getError($field), 'first error message is retained');

        $model->clearError($field);

        $I->assertFalse($model->hasErrors(), 'error message cleared');
        $I->assertFalse($model->isValid(), 'model still has not been validated, therefore still not considered valid');

        $model->setError($field, 'another error');

        $model->clearErrors(true);

        $I->assertTrue($model->isValid(), 'model initializes as validated only if explicitly set');
    }
}
