<?php

namespace mindplay\kissform;

use RuntimeException;

/**
 * This class represents a hidden input containing a cross-site request forgery (CSRF) token
 */
class TokenField extends Field implements RenderableField
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
     * @var string secret salt
     */
    public $secret;

    /**
     * @var bool[] map of tokens validated and consumed during the lifetime of this object
     *
     * @see verifyToken()
     */
    private $valid = array();

    /**
     * @param string $name   field name
     * @param string $secret secret salt
     */
    public function __construct($name, $secret)
    {
        parent::__construct($name);

        $this->secret = $secret;
        $this->timestamp = time();
    }

    /**
     * @return string new CSRF token
     */
    public function createToken()
    {
        $salt = sha1(microtime(true) . rand(0,9999999));

        $hash = $this->hash($salt, $this->timestamp);

        $token = base64_encode(json_encode(array(
            self::KEY_TIMESTAMP => $this->timestamp,
            self::KEY_SALT      => $salt,
            self::KEY_HASH      => $hash,
        )));

        $this->registerToken($token);

        return $token;
    }

    /**
     * @param string $token posted CSRF token
     *
     * @return bool true, if valid; otherwise false
     */
    public function checkToken($token)
    {
        if (!$this->verifyToken($token)) {
            return false; // no such token was issued
        }

        $data = @json_decode(base64_decode($token), true);

        if (!isset($data[self::KEY_TIMESTAMP], $data[self::KEY_SALT], $data[self::KEY_HASH])) {
            return false; // invalid token
        }

        $timestamp = $data[self::KEY_TIMESTAMP];
        $salt = $data[self::KEY_SALT];
        $hash = $data[self::KEY_HASH];

        if ($hash !== $this->hash($salt, $timestamp)) {
            return false; // wrong hash
        }

        $time = $this->timestamp - $timestamp;

        return ($time >= $this->valid_from)
            && ($time <= $this->valid_to);
    }

    /**
     * Use the given InputRenderer to render an HTML input for this Field, using
     * state obtained from the given InputModel, and optionally overriding a given
     * set of HTML attribute values.
     *
     * @param InputRenderer $renderer
     * @param InputModel    $model
     * @param string[]      $attr map of HTML attributes
     *
     * @return string HTML
     */
    public function renderInput(InputRenderer $renderer, InputModel $model, array $attr)
    {
        return $renderer->tag(
            'input',
            $attr + array(
                'type' => 'hidden',
                'name' => $renderer->createName($this),
                'value' => $this->createToken(),
            )
        );
    }

    /**
     * @param string $salt
     * @param int $timestamp
     * @return string
     */
    private function hash($salt, $timestamp)
    {
        $data = $salt . $timestamp . @$_SERVER['REMOTE_ADDR'] . @$_SERVER['HTTP_USER_AGENT'] . session_id();

        return hash_hmac(self::HASH_ALGO, $data, $this->secret);
    }

    /**
     * Register a new token in session variables.
     *
     * @param string $token
     *
     * @return void
     *
     * @see verifyToken()
     */
    private function registerToken($token)
    {
        if (!session_id()) {
            throw new RuntimeException("no active session");
        }

        if (!isset($_SESSION[__CLASS__])) {
            $_SESSION[__CLASS__] = array();
        }

        $_SESSION[__CLASS__][$token] = true;

        if (count($_SESSION[__CLASS__]) > 10) {
            // truncate garbage tokens (which may accummulate if the user keeps hitting "refresh")

            $_SESSION[__CLASS__] = array_slice($_SESSION[__CLASS__], -10, null, true);
        }
    }

    /**
     * Verify a previously registered token, and remove it from session variables.
     *
     * @param string $token
     *
     * @return bool true, if the given token exists
     *
     * @see registerToken()
     */
    private function verifyToken($token)
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
}
