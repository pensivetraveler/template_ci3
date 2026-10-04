<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Opaque_token
 * ----------------------------------------------------------
 * API Token Generate/Validation
 */

class Opaque_token
{
    protected $CI;

    public function __construct()
    {
        $this->CI =& get_instance();
    }

    /**
     * Generate random opaque token.
     *
     * @param int $length
     * @return string
     * @throws Exception
     */
    public function generate($length = 40): string
    {
        $length = (int) $length;

        if ($length < 32) {
            $length = 32;
        }

        $bytes = (int) ceil($length / 2);

        return substr(bin2hex(random_bytes($bytes)), 0, $length);
    }

    /**
     * Generate base64url opaque token.
     * Useful for refresh tokens or longer secrets.
     *
     * @param int $bytes
     * @return string
     * @throws Exception
     */
    public function generateBase64Url($bytes = 32): string
    {
        $token = random_bytes((int) $bytes);

        return rtrim(strtr(base64_encode($token), '+/', '-_'), '=');
    }

    /**
     * Hash token before storing it.
     * Optional, but recommended for refresh tokens.
     *
     * @param string $token
     * @return string
     */
    public function hash(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * Verify plain token against stored hash.
     *
     * @param string $token
     * @param string $hash
     * @return bool
     */
    public function verifyHash(string $token, string $hash): bool
    {
        return hash_equals($hash, $this->hash($token));
    }
}
