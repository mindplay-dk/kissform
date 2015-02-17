<?php

namespace mindplay\kissform;

/**
 * This class represents a cross-site request forgery (CSRF) token
 */
class CsrfField extends TextField
{
    /** @var string hash algorithm */
    const HASH_ALGO = 'sha512';

    /** @var string timestamp key */
    const KEY_TIMESTAMP = 'T';

    /** @var string salt key */
    const KEY_SALT = 'S';

    /** @var string hash key */
    const KEY_HASH = 'H';

    /**
     * @var int timestamp
     */
    public $timestamp;

    /**
     * @var int token is valid n seconds from now (prevents submission quicker than a human)
     */
    public $valid_from = 5;

    /**
     * @var int token is valid until n seconds from now (token expires after this time)
     */
    public $valid_to = 1200;

    /**
     * @param string $name field name
     */
    public function __construct($name)
    {
        parent::__construct($name);

        $this->timestamp = time();
    }

    /**
     * @param string $secret application-specific secret salt
     *
     * @return string new CSRF token
     */
    public function createToken($secret)
    {
        $salt = sha1(microtime(true) . rand(0,9999999));

        $hash = hash_hmac(self::HASH_ALGO, $salt . $this->timestamp, $secret);

        return base64_encode(json_encode(array(
            self::KEY_TIMESTAMP => $this->timestamp,
            self::KEY_SALT => $salt,
            self::KEY_HASH => $hash,
        )));
    }

    /**
     * @param string $token  posted CSRF token
     * @param string $secret application-specific secret salt
     *
     * @return bool true, if valid; otherwise false
     */
    public function checkToken($token, $secret)
    {
        $data = @json_decode(base64_decode($token), true);

        if (!isset($data[self::KEY_TIMESTAMP], $data[self::KEY_SALT], $data[self::KEY_HASH])) {
            return false; // invalid token
        }

        $timestamp = $data[self::KEY_TIMESTAMP];
        $salt = $data[self::KEY_SALT];
        $hash = $data[self::KEY_HASH];

        if ($hash !== hash_hmac(self::HASH_ALGO, $salt . $timestamp, $secret)) {
            return false; // wrong hash
        }

        $time = $this->timestamp - $timestamp;

        return ($time >= $this->valid_from)
            && ($time <= $this->valid_to);
    }
}
