<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * CodeIgniter 3 Curl Library
 *
 * Target:
 * - CodeIgniter 3
 * - PHP 7.4
 *
 * Features:
 * - GET / POST / PUT / PATCH / DELETE
 * - JSON body request
 * - Form-urlencoded request
 * - Custom headers
 * - Bearer token
 * - Basic auth
 * - SSL options
 * - Debug / response metadata
 */
class Curl_lib
{
    /** @var CI_Controller */
    protected $_ci;

    /** @var resource|null */
    protected $session = null;

    /** @var string */
    protected $url = '';

    /** @var array */
    protected $options = array();

    /** @var array */
    protected $headers = array();

    /** @var string */
    protected $response = '';

    /** @var string */
    protected $last_response = '';

    /** @var int|null */
    public $error_code = null;

    /** @var string */
    public $error_string = '';

    /** @var array */
    public $info = array();

    public function __construct($config = array())
    {
        $this->_ci = &get_instance();

        if (!$this->is_enabled()) {
            log_message('error', 'Curl_library: PHP cURL extension is not enabled.');
        }

        if (!empty($config) && is_array($config)) {
            if (!empty($config['url'])) {
                $this->create($config['url']);
            }

            if (!empty($config['options']) && is_array($config['options'])) {
                $this->options($config['options']);
            }
        }
    }

    public function __call($method, $arguments)
    {
        if (in_array($method, array('simple_get', 'simple_post', 'simple_put', 'simple_patch', 'simple_delete'), true)) {
            $verb = str_replace('simple_', '', $method);
            array_unshift($arguments, $verb);
            return call_user_func_array(array($this, '_simple_call'), $arguments);
        }

        throw new BadMethodCallException('Undefined method: ' . $method);
    }

    /**
     * Simple request helper
     *
     * @param string $method
     * @param string $url
     * @param array|string $params
     * @param array $options
     * @param array $headers
     * @return string|false
     */
    public function _simple_call($method, $url, $params = array(), $options = array(), $headers = array())
    {
        $method = strtolower($method);

        if ($method === 'get') {
            if (is_array($params) && !empty($params)) {
                $query_string = http_build_query($params, '', '&');
                $url .= (strpos($url, '?') === false ? '?' : '&') . $query_string;
            }

            $this->create($url);
        } else {
            $this->create($url);

            switch ($method) {
                case 'post':
                    $this->post($params);
                    break;
                case 'put':
                    $this->put($params);
                    break;
                case 'patch':
                    $this->patch($params);
                    break;
                case 'delete':
                    $this->delete($params);
                    break;
            }
        }

        if (!empty($headers)) {
            foreach ($headers as $name => $value) {
                if (is_int($name)) {
                    $this->http_header($value);
                } else {
                    $this->http_header($name, $value);
                }
            }
        }

        $this->options($options);

        return $this->execute();
    }

    /**
     * Create cURL session
     *
     * @param string $url
     * @return $this
     */
    public function create($url)
    {
        if (!preg_match('!^\w+://!i', $url)) {
            $this->_ci->load->helper('url');
            $url = site_url($url);
        }

        $this->url = $url;
        $this->session = curl_init($this->url);

        return $this;
    }

    public function get($params = array(), $options = array())
    {
        if (!empty($params)) {
            $query_string = is_array($params) ? http_build_query($params, '', '&') : (string) $params;
            $this->url .= (strpos($this->url, '?') === false ? '?' : '&') . $query_string;
            curl_setopt($this->session, CURLOPT_URL, $this->url);
        }

        $this->http_method('GET');
        $this->options($options);

        return $this;
    }

    public function post($params = array(), $options = array())
    {
        if (is_array($params)) {
            $params = http_build_query($params, '', '&');
        }

        $this->http_method('POST');
        $this->option(CURLOPT_POST, true);
        $this->option(CURLOPT_POSTFIELDS, $params);
        $this->options($options);

        return $this;
    }

    public function put($params = array(), $options = array())
    {
        return $this->custom_request('PUT', $params, $options);
    }

    public function patch($params = array(), $options = array())
    {
        return $this->custom_request('PATCH', $params, $options);
    }

    public function delete($params = array(), $options = array())
    {
        return $this->custom_request('DELETE', $params, $options);
    }

    public function custom_request($method, $params = array(), $options = array())
    {
        if (is_array($params)) {
            $params = http_build_query($params, '', '&');
        }

        $this->http_method($method);
        $this->option(CURLOPT_POSTFIELDS, $params);
        $this->options($options);

        return $this;
    }

    /**
     * JSON request helper
     *
     * @param string $method
     * @param array $data
     * @param array $options
     * @return $this
     */
    public function json($method, array $data = array(), $options = array())
    {
        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $this->http_header('Content-Type', 'application/json');
        $this->http_header('Accept', 'application/json');
        $this->http_method($method);
        $this->option(CURLOPT_POSTFIELDS, $payload);
        $this->options($options);

        return $this;
    }

    public function json_post(array $data = array(), $options = array())
    {
        return $this->json('POST', $data, $options);
    }

    public function json_put(array $data = array(), $options = array())
    {
        return $this->json('PUT', $data, $options);
    }

    public function json_patch(array $data = array(), $options = array())
    {
        return $this->json('PATCH', $data, $options);
    }

    public function json_delete(array $data = array(), $options = array())
    {
        return $this->json('DELETE', $data, $options);
    }

    public function set_cookies($params = array())
    {
        if (is_array($params)) {
            $params = http_build_query($params, '', '&');
        }

        $this->option(CURLOPT_COOKIE, $params);

        return $this;
    }

    public function http_header($header, $content = null)
    {
        $this->headers[] = ($content === null) ? $header : ($header . ': ' . $content);
        return $this;
    }

    public function set_headers(array $headers = array())
    {
        foreach ($headers as $key => $value) {
            if (is_int($key)) {
                $this->http_header($value);
            } else {
                $this->http_header($key, $value);
            }
        }

        return $this;
    }

    public function bearer_token($token)
    {
        return $this->http_header('Authorization', 'Bearer ' . $token);
    }

    public function http_method($method)
    {
        $this->option(CURLOPT_CUSTOMREQUEST, strtoupper($method));
        return $this;
    }

    public function http_login($username = '', $password = '', $type = 'BASIC')
    {
        $type = strtoupper($type);
        $auth_const = defined('CURLAUTH_' . $type) ? constant('CURLAUTH_' . $type) : CURLAUTH_BASIC;

        $this->option(CURLOPT_HTTPAUTH, $auth_const);
        $this->option(CURLOPT_USERPWD, $username . ':' . $password);

        return $this;
    }

    public function proxy($url = '', $port = 80)
    {
        $this->option(CURLOPT_HTTPPROXYTUNNEL, true);
        $this->option(CURLOPT_PROXY, $url . ':' . $port);

        return $this;
    }

    public function proxy_login($username = '', $password = '')
    {
        $this->option(CURLOPT_PROXYUSERPWD, $username . ':' . $password);
        return $this;
    }

    public function ssl($verify_peer = true, $verify_host = 2, $path_to_cert = null)
    {
        $this->option(CURLOPT_SSL_VERIFYPEER, (bool) $verify_peer);
        $this->option(CURLOPT_SSL_VERIFYHOST, (int) $verify_host);

        if ($verify_peer && !empty($path_to_cert)) {
            $real_path = realpath($path_to_cert);
            if ($real_path !== false) {
                $this->option(CURLOPT_CAINFO, $real_path);
            }
        }

        return $this;
    }

    public function timeout($seconds)
    {
        $this->option(CURLOPT_TIMEOUT, (int) $seconds);
        return $this;
    }

    public function connect_timeout($seconds)
    {
        $this->option(CURLOPT_CONNECTTIMEOUT, (int) $seconds);
        return $this;
    }

    public function follow_location($flag = true)
    {
        $this->option(CURLOPT_FOLLOWLOCATION, (bool) $flag);
        return $this;
    }

    public function user_agent($user_agent)
    {
        $this->option(CURLOPT_USERAGENT, $user_agent);
        return $this;
    }

    public function options($options = array())
    {
        foreach ($options as $option_code => $option_value) {
            $this->option($option_code, $option_value);
        }

        if (is_resource($this->session) || $this->session instanceof CurlHandle || is_object($this->session)) {
            curl_setopt_array($this->session, $this->options);
        }

        return $this;
    }

    public function option($code, $value, $prefix = 'OPT')
    {
        if (is_string($code) && !is_numeric($code)) {
            $constant_name = 'CURLOPT_' . strtoupper($code);
            if (defined($constant_name)) {
                $code = constant($constant_name);
            }
        }

        $this->options[$code] = $value;

        return $this;
    }

    /**
     * Execute request
     *
     * @param bool $decode_json
     * @return mixed
     */
    public function execute($decode_json = false)
    {
        if (!$this->session) {
            $this->error_code = -1;
            $this->error_string = 'cURL session has not been created.';
            return false;
        }

        if (!isset($this->options[CURLOPT_TIMEOUT])) {
            $this->options[CURLOPT_TIMEOUT] = 30;
        }

        if (!isset($this->options[CURLOPT_RETURNTRANSFER])) {
            $this->options[CURLOPT_RETURNTRANSFER] = true;
        }

        if (!isset($this->options[CURLOPT_FAILONERROR])) {
            $this->options[CURLOPT_FAILONERROR] = false;
        }

        if (!isset($this->options[CURLOPT_HEADER])) {
            $this->options[CURLOPT_HEADER] = false;
        }

        if (!ini_get('open_basedir') && !ini_get('safe_mode') && !isset($this->options[CURLOPT_FOLLOWLOCATION])) {
            $this->options[CURLOPT_FOLLOWLOCATION] = true;
        }

        if (!empty($this->headers)) {
            $this->options[CURLOPT_HTTPHEADER] = $this->headers;
        }

        curl_setopt_array($this->session, $this->options);

        $this->response = curl_exec($this->session);

        $this->info = curl_getinfo($this->session);

        if ($this->response === false) {
            $this->error_code = curl_errno($this->session);
            $this->error_string = curl_error($this->session);

            curl_close($this->session);
            $this->set_defaults();

            return false;
        }

        $this->last_response = $this->response;

        curl_close($this->session);
        $this->set_defaults();

        if ($decode_json === true) {
            $decoded = json_decode($this->last_response, true);
            return (json_last_error() === JSON_ERROR_NONE) ? $decoded : false;
        }

        return $this->last_response;
    }

    /**
     * Execute and return structured result
     *
     * @param bool $decode_json
     * @return array
     */
    public function execute_with_meta($decode_json = false)
    {
        $body = $this->execute(false);

        $info = $this->info;

        $result = array(
            'success'      => ($body !== false),
            'status_code'  => isset($this->info['http_code']) ? (int) $this->info['http_code'] : 0,
            'headers'      => $this->headers,
            'body'         => $body,
            'json'         => null,
            'error_code'   => $this->error_code,
            'error_string' => $this->error_string,
            'curl_info' => [
                'namelookup_ms' => ($info['namelookup_time'] ?? 0) * 1000,
                'connect_ms' => ($info['connect_time'] ?? 0) * 1000,
                'appconnect_ms' => ($info['appconnect_time'] ?? 0) * 1000,
                'pretransfer_ms' => ($info['pretransfer_time'] ?? 0) * 1000,
                'starttransfer_ms' => ($info['starttransfer_time'] ?? 0) * 1000,
                'total_ms' => ($info['total_time'] ?? 0) * 1000,
                'primary_ip' => $info['primary_ip'] ?? '',
                'http_version' => $info['http_version'] ?? null,
            ],
        );

        if ($body !== false && $decode_json === true) {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result['json'] = $decoded;
            }
        }

        return $result;
    }

    public function is_enabled()
    {
        return function_exists('curl_init');
    }

    public function debug()
    {
        echo '<h3>Response</h3>';
        echo '<pre>' . htmlspecialchars((string) $this->last_response, ENT_QUOTES, 'UTF-8') . '</pre>';

        if (!empty($this->error_string)) {
            echo '<h3>Error</h3>';
            echo '<p><strong>Code:</strong> ' . (int) $this->error_code . '</p>';
            echo '<p><strong>Message:</strong> ' . htmlspecialchars($this->error_string, ENT_QUOTES, 'UTF-8') . '</p>';
        }

        echo '<h3>Info</h3>';
        echo '<pre>';
        print_r($this->info);
        echo '</pre>';
    }

    public function debug_request()
    {
        return array(
            'url'     => $this->url,
            'headers' => $this->headers,
            'options' => $this->options,
        );
    }

    protected function set_defaults()
    {
        $this->response = '';
        $this->headers = array();
        $this->options = array();
        $this->session = null;
    }
}
