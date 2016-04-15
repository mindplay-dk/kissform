<?php

namespace mindplay\kissform\Test;

use mindplay\kissform\Facets\TokenStoreInterface;
use mindplay\kissform\Fields\HiddenField;
use mindplay\kissform\Fields\TokenField;
use mindplay\kissform\Framework\TokenService;
use mindplay\kissform\InputModel;
use mindplay\kissform\InputRenderer;
use mindplay\kissform\InputValidation;
use mindplay\kissform\Validators\CheckToken;
use ReflectionProperty;
use UnitTester;

class TokenCest
{
    /**
     * @return TokenStoreInterface
     */
    private function createMockTokenStore()
    {
        return new MockTokenStore();
    }

    public function validateToken(UnitTester $I)
    {
        $field = new HiddenField('value');
        $service = new TokenService($this->createMockTokenStore(), 'abc123');

        $token_name = 'abc123';

        $not_valid_yet = $service->createToken($token_name);

        $I->testValidator(
            $field,
            new CheckToken($service),
            [],
            [$not_valid_yet]
        );

        $service->timestamp += $service->valid_from;
        $valid = $not_valid_yet;

        $I->testValidator(
            $field,
            new CheckToken($service),
            [$valid],
            ['1' . $valid] // mangled token
        );

        $model = InputModel::create([]);

        $validator = new InputValidation($model);

        $validator->validate($field, new CheckToken($service));

        $I->assertTrue($model->hasError($field),
            "field " . get_class($field) . " rejects on missing value");
    }

    public function detectTokenFieldExpiration(UnitTester $I)
    {
        $service = new TokenService($this->createMockTokenStore(), 'abc123');

        $token_name = 'abc123';

        $token = $service->createToken($token_name);
        $I->assertTrue(strlen($token) > 0, 'it creates a token', base64_decode($token));

        $I->assertTrue($token !== $service->createToken($token_name), 'it creates unique tokens');

        $token = $service->createToken($token_name);

        $I->assertTrue($service->checkToken($token_name, $token) === false, 'token invalid when submitted too soon');

        $timestamp = $service->timestamp;

        $service->timestamp = $timestamp + $service->valid_from;

        $I->assertTrue($service->checkToken($token_name, $token) === true, 'token valid when submitted before expiration');

        $service->timestamp = $timestamp + $service->valid_to;

        $I->assertTrue($service->checkToken($token_name, $token) === true, 'token valid when submitted on time');

        $secret_stealer = new ReflectionProperty($service, 'secret');
        $secret_stealer->setAccessible(true);

        $secret = $secret_stealer->getValue($service); // save correct secret
        $secret_stealer->setValue($service, $secret . '1'); // wrongify
        $I->assertTrue($service->checkToken($token_name, $token) === false, 'token invalid when using the wrong secret');
        $secret_stealer->setValue($service, $secret); // restore correct secret
        $I->assertTrue($service->checkToken($token_name, '1' . $token) === false, 'token invalid after tampering');

        $service->timestamp = $timestamp + $service->valid_to + 1;

        $I->assertTrue($service->checkToken($token_name, $token) === false, 'token invalid when submitted after expiration');
    }

    public function integrateWithValidatorAndRenderer(UnitTester $I)
    {
        $SECRET_SALT = 'abc123';
        $TOKEN_NAME = 'my unique form name goes here';
        $TOKEN_STORE = $this->createMockTokenStore();

        // emulating a GET:

        $service = new TokenService($TOKEN_STORE, $SECRET_SALT);

        $field = new TokenField($TOKEN_NAME, $service);

        $model = InputModel::create();

        $renderer = new InputRenderer($model);

        $html = $renderer->render($field);
        
        preg_match('/name="([^"]*).*value="([^"]*)/', $html, $matches);

        $name = $matches[1];
        $value = $matches[2];

        unset($service, $field, $model);

        // emulating a POST:

        $service = new TokenService($TOKEN_STORE, $SECRET_SALT);

        $service->timestamp += $service->valid_from; // time goes by until the token becomes valid

        $field = new TokenField($TOKEN_NAME, $service);

        $model = InputModel::create([$name => $value]);

        $validator = new InputValidation($model);

        $validator->check($field);

        $I->assertTrue($model->isValid());

        // an extra assertion, kinda duplicating a previous test, just for extra safety:

        $service->timestamp += $service->valid_to; // time goes by and makes the token expire

        $validator->check($field);

        $I->assertFalse($model->isValid());
    }
}
