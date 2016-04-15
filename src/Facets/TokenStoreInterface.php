<?php

namespace mindplay\kissform\Facets;

/**
 * This interface defines how form tokens are stored in user session state.
 */
interface TokenStoreInterface
{
    /**
     * Register a new token as part of user session state.
     *
     * @param string $token
     *
     * @return void
     */
    public function registerToken($token);

    /**
     * Verify a previously registered token, and remove it from session state.
     *
     * @param string $token
     *
     * @return bool true, if the given token exists
     */
    public function verifyToken($token);

    /**
     * @return string a client identification salt (for use in token hashes)
     */
    public function getClientSalt();
}
