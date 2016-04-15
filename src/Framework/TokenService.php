<?php

namespace mindplay\kissform\Framework;

use mindplay\kissform\Facets\TokenServiceInterface;
use mindplay\kissform\Facets\TokenStoreInterface;

class TokenService implements TokenServiceInterface
{
    /**
     * @var string hash algorithm
     */
    const HASH_ALGO = 'sha512';

    /**
     * @var string timestamp key
     */
    const KEY_TIMESTAMP = 'T';

    /**
     * @var string salt key
     */
    const KEY_SALT = 'S';

    /**
     * @var string hash key
     */
    const KEY_HASH = 'H';

    /**
     * @var int token is valid n seconds from now (prevents submission quicker than a human)
     */
    public $valid_from = 5;

    /**
     * @var int token is valid until n seconds from now (token expires after this time)
     */
    public $valid_to = 1200;

    /**
     * @var string secret salt
     */
    private $secret;

    /**
     * @var TokenStoreInterface
     */
    private $store;

    /**
     * @var int timestamp
     */
    public $timestamp;

    /**
     * @param TokenStoreInterface $store
     * @param string              $secret secret salt (unique to your form or controller)
     */
    public function __construct(TokenStoreInterface $store, $secret)
    {
        $this->secret = $secret;
        $this->store = $store;
        $this->timestamp = time();
    }

    /**
     * @inheritdoc
     */
    public function createToken($name)
    {
        $salt = sha1(microtime(true) . rand(0, 9999999));

        $hash = $this->hash($name, $salt, $this->timestamp);

        $token = base64_encode(json_encode([
            self::KEY_TIMESTAMP => $this->timestamp,
            self::KEY_SALT      => $salt,
            self::KEY_HASH      => $hash,
        ]));

        $this->store->registerToken($token);

        return $token;
    }

    /**
     * @inheritdoc
     */
    public function checkToken($name, $token)
    {
        if (!$this->store->verifyToken($token)) {
            return false; // no such token was issued
        }

        $data = @json_decode(base64_decode($token), true);

        if (!isset($data[self::KEY_TIMESTAMP], $data[self::KEY_SALT], $data[self::KEY_HASH])) {
            return false; // invalid token
        }

        $timestamp = $data[self::KEY_TIMESTAMP];
        $salt = $data[self::KEY_SALT];
        $hash = $data[self::KEY_HASH];

        if ($hash !== $this->hash($name, $salt, $timestamp)) {
            return false; // wrong hash
        }

        $time = $this->timestamp - $timestamp;

        return ($time >= $this->valid_from)
            && ($time <= $this->valid_to);
    }

    /**
     * @param string $name
     * @param string $salt
     * @param int    $timestamp
     *
     * @return string
     */
    private function hash($name, $salt, $timestamp)
    {
        $data = $salt . $timestamp . $this->store->getClientSalt();

        return hash_hmac(self::HASH_ALGO, $data, $this->secret);
    }
}
