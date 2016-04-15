<?php

namespace mindplay\kissform\Facets;

/**
 * This defines the public interface of a cross-site request forgery (CSRF) token service.
 */
interface TokenServiceInterface
{
    /**
     * @param string $name token name, used to salt the token (could be a form or controller name or random string)
     *
     * @return string new CSRF token
     */
    public function createToken($name);

    /**
     * @param string $name token name, as given when the token was created
     * @param string $token posted CSRF token
     *
     * @return bool true, if valid; otherwise false
     */
    public function checkToken($name, $token);
}
