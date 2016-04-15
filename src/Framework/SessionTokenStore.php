<?php

namespace mindplay\kissform\Framework;

use mindplay\kissform\Facets\TokenStoreInterface;
use RuntimeException;

/**
 * This class implements a token store using `$_SESSION`.
 */
class SessionTokenStore implements TokenStoreInterface
{
    /**
     * @var int hard limit for unique tokens stored per user session
     */
    const HARD_LIMIT = 10;

    /**
     * @var bool[] map of tokens validated and consumed during the lifetime of this object
     */
    private $valid = [];

    /**
     * @inheritdoc
     *
     * @throws RuntimeException
     *
     * @SuppressWarnings(Superglobals)
     */
    public function registerToken($token)
    {
        if (!session_id()) {
            throw new RuntimeException("no active session");
        }

        if (!isset($_SESSION[__CLASS__])) {
            $_SESSION[__CLASS__] = [];
        }

        $_SESSION[__CLASS__][$token] = true;

        if (count($_SESSION[__CLASS__]) > self::HARD_LIMIT) {
            // truncate garbage tokens (which may accummulate if the user keeps hitting "refresh")

            $_SESSION[__CLASS__] = array_slice($_SESSION[__CLASS__], -self::HARD_LIMIT, null, true);
        }
    }

    /**
     * @inheritdoc
     *
     * @throws RuntimeException
     *
     * @SuppressWarnings(Superglobals)
     */
    public function verifyToken($token)
    {
        if (!session_id()) {
            throw new RuntimeException("no active session");
        }

        if (isset($_SESSION[__CLASS__][$token])) {
            unset($_SESSION[__CLASS__][$token]);

            $this->valid[$token] = true;
        }

        return isset($this->valid[$token]);
    }

    /**
     * @inheritdoc
     *
     * @SuppressWarnings(Superglobals)
     */
    public function getClientSalt()
    {
        return @$_SERVER['REMOTE_ADDR'] . @$_SERVER['HTTP_USER_AGENT'] . session_id();
    }
}
