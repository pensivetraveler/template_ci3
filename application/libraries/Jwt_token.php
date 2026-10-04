<?php defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Jwt_token
 * ----------------------------------------------------------
 * API JWT Token Generate/Validation
 */

require_once APPPATH . 'third_party/php-jwt/JWT.php';
require_once APPPATH . 'third_party/php-jwt/BeforeValidException.php';
require_once APPPATH . 'third_party/php-jwt/ExpiredException.php';
require_once APPPATH . 'third_party/php-jwt/SignatureInvalidException.php';

use \Firebase\JWT\JWT;

class Jwt_token
{
    protected $CI;

    /**
     * Token Key
     */
    protected $token_key;

    /**
     * Token algorithm
     */
    protected $token_algorithm;

    /**
     * Token Prefix
     */
    protected $token_prefix;

    /**
     * Token Request Header Name
     */
    protected $token_header;

    /**
     * Token Expire Time
     */
    protected $token_expire_time;

    public function __construct($params = [])
    {
        $config = !empty($params['config']) ? $params['config'] : 'jwt';

        $this->CI =& get_instance();

        /**
         * jwt config file load
         */
        $this->CI->load->config($config);

        /**
         * Load Config Items Values
         */
        $this->token_key         = $this->CI->config->item('jwt_key');
        $this->token_algorithm   = $this->CI->config->item('jwt_algorithm') ?: 'HS256';
        $this->token_prefix      = $this->CI->config->item('token_prefix') ?: 'Bearer';
        $this->token_header      = $this->CI->config->item('token_header') ?: 'Authorization';
        $this->token_expire_time = (int) ($this->CI->config->item('token_expire_time') ?: 3600);
    }

    public function encode(array $payload): string
    {
        return JWT::encode($payload, $this->token_key, $this->token_algorithm);
    }

    public function decode(string $token): object
    {
        return JWT::decode($token, $this->token_key, [$this->token_algorithm]);
    }

    public function generateAccessToken(array $data): string
    {
        $now = time();

        $payload = array_merge($data, [
            'iat' => $now,
            'exp' => $now + $this->token_expire_time,
        ]);

        return $this->encode($payload);
    }

    public function validateTokenFromHeader(): array
    {
        $headers = $this->CI->input->request_headers();
        $result = $this->extractTokenFromHeaders($headers);

        if ($result['status'] !== true) {
            return $result;
        }

        return $this->validateToken($result['token']);
    }

    public function validateToken(string $token): array
    {
        try {
            $decoded = $this->decode($token);

            return [
                'status' => true,
                'data'   => $decoded,
            ];
        } catch (Exception $e) {
            return [
                'status'  => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function extractTokenFromHeaders(array $headers): array
    {
        foreach ($headers as $headerName => $headerValue) {
            if (strtolower(trim($headerName)) !== strtolower(trim($this->token_header))) {
                continue;
            }

            $headerValue = trim($headerValue);

            if ($this->token_prefix) {
                $pattern = '/^' . preg_quote($this->token_prefix, '/') . '\s+(.+)$/i';

                if (!preg_match($pattern, $headerValue, $matches)) {
                    return [
                        'status'  => false,
                        'message' => 'Token prefix is invalid.',
                    ];
                }

                return [
                    'status' => true,
                    'token'  => trim($matches[1]),
                ];
            }

            return [
                'status' => true,
                'token'  => $headerValue,
            ];
        }

        return [
            'status'  => false,
            'message' => 'Token is not defined.',
        ];
    }
}
