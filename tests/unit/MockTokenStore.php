<?php

namespace mindplay\kissform\Test;

use mindplay\kissform\Facets\TokenStoreInterface;

class MockTokenStore implements TokenStoreInterface
{
    private $tokens = [];

    public function registerToken($token)
    {
        $this->tokens[$token] = true;
    }

    public function verifyToken($token)
    {
        return isset($this->tokens[$token]);
    }

    public function getClientSalt()
    {
        return 'abc123';
    }
}
