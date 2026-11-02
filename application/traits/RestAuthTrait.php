<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * RestController 인증: 인증 방식별 검사(basic·digest·ldap·세션), IP 목록, API 키 권한
 */
trait RestAuthTrait
{
    /**
     * Check if there is a specific auth type set for the current class/method/HTTP-method being called
     *
     * @access protected
     * @return bool
     */
    protected function _auth_override_check()
    {
        // Assign the class/method auth type override array from the config
        $auth_override_class_method = $this->config->item('auth_override_class_method');

        // Check to see if the override array is even populated
        if ( ! empty($auth_override_class_method))
        {
            // Check for wildcard flag for rules for classes
            if ( ! empty($auth_override_class_method[$this->router->class]['*'])) // Check for class overrides
            {
                // No auth override found, prepare nothing but send back a TRUE override flag
                if ($auth_override_class_method[$this->router->class]['*'] === 'none')
                {
                    return TRUE;
                }

                // Basic auth override found, prepare basic
                if ($auth_override_class_method[$this->router->class]['*'] === 'basic')
                {
                    $this->_prepare_basic_auth();

                    return TRUE;
                }

                // Digest auth override found, prepare digest
                if ($auth_override_class_method[$this->router->class]['*'] === 'digest')
                {
                    $this->_prepare_digest_auth();

                    return TRUE;
                }

                // Session auth override found, check session
                if ($auth_override_class_method[$this->router->class]['*'] === 'session')
                {
                    $this->_check_php_session();

                    return TRUE;
                }

                // Whitelist auth override found, check client's ip against config whitelist
                if ($auth_override_class_method[$this->router->class]['*'] === 'whitelist')
                {
                    $this->_check_whitelist_auth();

                    return TRUE;
                }
            }

            // Check to see if there's an override value set for the current class/method being called
            if ( ! empty($auth_override_class_method[$this->router->class][$this->router->method]))
            {
                // None auth override found, prepare nothing but send back a TRUE override flag
                if ($auth_override_class_method[$this->router->class][$this->router->method] === 'none')
                {
                    return TRUE;
                }

                // Basic auth override found, prepare basic
                if ($auth_override_class_method[$this->router->class][$this->router->method] === 'basic')
                {
                    $this->_prepare_basic_auth();

                    return TRUE;
                }

                // Digest auth override found, prepare digest
                if ($auth_override_class_method[$this->router->class][$this->router->method] === 'digest')
                {
                    $this->_prepare_digest_auth();

                    return TRUE;
                }

                // Session auth override found, check session
                if ($auth_override_class_method[$this->router->class][$this->router->method] === 'session')
                {
                    $this->_check_php_session();

                    return TRUE;
                }

                // Whitelist auth override found, check client's ip against config whitelist
                if ($auth_override_class_method[$this->router->class][$this->router->method] === 'whitelist')
                {
                    $this->_check_whitelist_auth();

                    return TRUE;
                }
            }
        }

        // Assign the class/method/HTTP-method auth type override array from the config
        $auth_override_class_method_http = $this->config->item('auth_override_class_method_http');

        // Check to see if the override array is even populated
        if ( ! empty($auth_override_class_method_http))
        {
            // check for wildcard flag for rules for classes
            if ( ! empty($auth_override_class_method_http[$this->router->class]['*'][$this->request->method]))
            {
                // None auth override found, prepare nothing but send back a TRUE override flag
                if ($auth_override_class_method_http[$this->router->class]['*'][$this->request->method] === 'none')
                {
                    return TRUE;
                }

                // Basic auth override found, prepare basic
                if ($auth_override_class_method_http[$this->router->class]['*'][$this->request->method] === 'basic')
                {
                    $this->_prepare_basic_auth();

                    return TRUE;
                }

                // Digest auth override found, prepare digest
                if ($auth_override_class_method_http[$this->router->class]['*'][$this->request->method] === 'digest')
                {
                    $this->_prepare_digest_auth();

                    return TRUE;
                }

                // Session auth override found, check session
                if ($auth_override_class_method_http[$this->router->class]['*'][$this->request->method] === 'session')
                {
                    $this->_check_php_session();

                    return TRUE;
                }

                // Whitelist auth override found, check client's ip against config whitelist
                if ($auth_override_class_method_http[$this->router->class]['*'][$this->request->method] === 'whitelist')
                {
                    $this->_check_whitelist_auth();

                    return TRUE;
                }
            }

            // Check to see if there's an override value set for the current class/method/HTTP-method being called
            if ( ! empty($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method]))
            {
                // None auth override found, prepare nothing but send back a TRUE override flag
                if ($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method] === 'none')
                {
                    return TRUE;
                }

                // Basic auth override found, prepare basic
                if ($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method] === 'basic')
                {
                    $this->_prepare_basic_auth();

                    return TRUE;
                }

                // Digest auth override found, prepare digest
                if ($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method] === 'digest')
                {
                    $this->_prepare_digest_auth();

                    return TRUE;
                }

                // Session auth override found, check session
                if ($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method] === 'session')
                {
                    $this->_check_php_session();

                    return TRUE;
                }

                // Whitelist auth override found, check client's ip against config whitelist
                if ($auth_override_class_method_http[$this->router->class][$this->router->method][$this->request->method] === 'whitelist')
                {
                    $this->_check_whitelist_auth();

                    return TRUE;
                }
            }
        }
        return FALSE;
    }

    /**
     * Perform LDAP Authentication
     *
     * @access protected
     * @param string $username The username to validate
     * @param string $password The password to validate
     * @return bool
     */
    protected function _perform_ldap_auth($username = '', $password = NULL)
    {
        if (empty($username))
        {
            log_message('debug', 'LDAP Auth: failure, empty username');
            return FALSE;
        }

        log_message('debug', 'LDAP Auth: Loading configuration');

        $this->config->load('ldap.php', TRUE);

        $ldap = [
            'timeout' => $this->config->item('timeout', 'ldap'),
            'host' => $this->config->item('server', 'ldap'),
            'port' => $this->config->item('port', 'ldap'),
            'rdn' => $this->config->item('binduser', 'ldap'),
            'pass' => $this->config->item('bindpw', 'ldap'),
            'basedn' => $this->config->item('basedn', 'ldap'),
        ];

        log_message('debug', 'LDAP Auth: Connect to ' . (isset($ldaphost) ? $ldaphost : '[ldap not configured]'));

        // Connect to the ldap server
        $ldapconn = ldap_connect($ldap['host'], $ldap['port']);
        if ($ldapconn)
        {
            log_message('debug', 'Setting timeout to '.$ldap['timeout'].' seconds');

            ldap_set_option($ldapconn, LDAP_OPT_NETWORK_TIMEOUT, $ldap['timeout']);

            log_message('debug', 'LDAP Auth: Binding to '.$ldap['host'].' with dn '.$ldap['rdn']);

            // Binding to the ldap server
            $ldapbind = ldap_bind($ldapconn, $ldap['rdn'], $ldap['pass']);

            // Verify the binding
            if ($ldapbind === FALSE)
            {
                log_message('error', 'LDAP Auth: bind was unsuccessful');
                return FALSE;
            }

            log_message('debug', 'LDAP Auth: bind successful');
        }

        // Search for user
        if (($res_id = ldap_search($ldapconn, $ldap['basedn'], "uid=$username")) === FALSE)
        {
            log_message('error', 'LDAP Auth: User '.$username.' not found in search');
            return FALSE;
        }

        if (ldap_count_entries($ldapconn, $res_id) !== 1)
        {
            log_message('error', 'LDAP Auth: Failure, username '.$username.'found more than once');
            return FALSE;
        }

        if (($entry_id = ldap_first_entry($ldapconn, $res_id)) === FALSE)
        {
            log_message('error', 'LDAP Auth: Failure, entry of search result could not be fetched');
            return FALSE;
        }

        if (($user_dn = ldap_get_dn($ldapconn, $entry_id)) === FALSE)
        {
            log_message('error', 'LDAP Auth: Failure, user-dn could not be fetched');
            return FALSE;
        }

        // User found, could not authenticate as user
        if (($link_id = ldap_bind($ldapconn, $user_dn, $password)) === FALSE)
        {
            log_message('error', 'LDAP Auth: Failure, username/password did not match: ' . $user_dn);
            return FALSE;
        }

        log_message('debug', 'LDAP Auth: Success '.$user_dn.' authenticated successfully');

        $this->_user_ldap_dn = $user_dn;

        ldap_close($ldapconn);

        return TRUE;
    }

    /**
     * Perform Library Authentication - Override this function to change the way the library is called
     *
     * @access protected
     * @param string $username The username to validate
     * @param string $password The password to validate
     * @return bool
     */
    protected function _perform_library_auth($username = '', $password = NULL)
    {
        if (empty($username))
        {
            log_message('error', 'Library Auth: Failure, empty username');
            return FALSE;
        }

        $auth_library_class = strtolower($this->config->item('auth_library_class'));
        $auth_library_function = strtolower($this->config->item('auth_library_function'));

        if (empty($auth_library_class))
        {
            log_message('debug', 'Library Auth: Failure, empty auth_library_class');
            return FALSE;
        }

        if (empty($auth_library_function))
        {
            log_message('debug', 'Library Auth: Failure, empty auth_library_function');
            return FALSE;
        }

        if (is_callable([$auth_library_class, $auth_library_function]) === FALSE)
        {
            $this->load->library($auth_library_class);
        }

        return $this->{$auth_library_class}->$auth_library_function($username, $password);
    }

    /**
     * Check if the user is logged in
     *
     * @access protected
     * @param string $username The user's name
     * @param bool|string $password The user's password
     * @return bool
     */
    protected function _check_login($username = NULL, $password = FALSE)
    {
        if (empty($username))
        {
            return FALSE;
        }

        $auth_source = strtolower($this->config->item('auth_source'));
        $rest_auth = strtolower($this->config->item('rest_auth'));
        $valid_logins = $this->config->item('rest_valid_logins');

        if ( ! $this->config->item('auth_source') && $rest_auth === 'digest')
        {
            // For digest we do not have a password passed as argument
            return md5($username.':'.$this->config->item('rest_realm').':'.(isset($valid_logins[$username]) ? $valid_logins[$username] : ''));
        }

        if ($password === FALSE)
        {
            return FALSE;
        }

        if ($auth_source === 'ldap')
        {
            log_message('debug', "Performing LDAP authentication for $username");

            return $this->_perform_ldap_auth($username, $password);
        }

        if ($auth_source === 'library')
        {
            log_message('debug', "Performing Library authentication for $username");

            return $this->_perform_library_auth($username, $password);
        }

        if (array_key_exists($username, $valid_logins) === FALSE)
        {
            return FALSE;
        }

        if ($valid_logins[$username] !== $password)
        {
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Check to see if the user is logged in with a PHP session key
     *
     * @access protected
     * @return void
     */
    protected function _check_php_session()
    {
        // Get the auth_source config item
        $key = $this->config->item('auth_source');

        // If false, then the user isn't logged in
        if ( ! $this->session->userdata($key))
        {
            // Display an error response
            $this->response([
                $this->config->item('rest_status_field_name') => FALSE,
                $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unauthorized')
            ], self::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Prepares for basic authentication
     *
     * @access protected
     * @return void
     */
    protected function _prepare_basic_auth()
    {
        // If whitelist is enabled it has the first chance to kick them out
        if ($this->config->item('rest_ip_whitelist_enabled'))
        {
            $this->_check_whitelist_auth();
        }

        // Returns NULL if the SERVER variables PHP_AUTH_USER and HTTP_AUTHENTICATION don't exist
        $username = $this->input->server('PHP_AUTH_USER');
        $http_auth = $this->input->server('HTTP_AUTHENTICATION');

        $password = NULL;
        if ($username !== NULL)
        {
            $password = $this->input->server('PHP_AUTH_PW');
        }
        elseif ($http_auth !== NULL)
        {
            // If the authentication header is set as basic, then extract the username and password from
            // HTTP_AUTHORIZATION e.g. my_username:my_password. This is passed in the .htaccess file
            if (strpos(strtolower($http_auth), 'basic') === 0)
            {
                // Search online for HTTP_AUTHORIZATION workaround to explain what this is doing
                list($username, $password) = explode(':', base64_decode(substr($this->input->server('HTTP_AUTHORIZATION'), 6)));
            }
        }

        // Check if the user is logged into the system
        if ($this->_check_login($username, $password) === FALSE)
        {
            $this->_force_login();
        }
    }

    /**
     * Prepares for digest authentication
     *
     * @access protected
     * @return void
     */
    protected function _prepare_digest_auth()
    {
        // If whitelist is enabled it has the first chance to kick them out
        if ($this->config->item('rest_ip_whitelist_enabled'))
        {
            $this->_check_whitelist_auth();
        }

        // We need to test which server authentication variable to use,
        // because the PHP ISAPI module in IIS acts different from CGI
        $digest_string = $this->input->server('PHP_AUTH_DIGEST');
        if ($digest_string === NULL)
        {
            $digest_string = $this->input->server('HTTP_AUTHORIZATION');
        }

        $unique_id = uniqid();

        // The $_SESSION['error_prompted'] variable is used to ask the password
        // again if none given or if the user enters wrong auth information
        if (empty($digest_string))
        {
            $this->_force_login($unique_id);
        }

        // We need to retrieve authentication data from the $digest_string variable
        $matches = [];
        preg_match_all('@(username|nonce|uri|nc|cnonce|qop|response)=[\'"]?([^\'",]+)@', $digest_string, $matches);
        $digest = (empty($matches[1]) || empty($matches[2])) ? [] : array_combine($matches[1], $matches[2]);

        // For digest authentication the library function should return already stored md5(username:restrealm:password) for that username see rest.php::auth_library_function config
        $username = $this->_check_login($digest['username'], TRUE);
        if (array_key_exists('username', $digest) === FALSE || $username === FALSE)
        {
            $this->_force_login($unique_id);
        }

        $md5 = md5(strtoupper($this->request->method).':'.$digest['uri']);
        $valid_response = md5($username.':'.$digest['nonce'].':'.$digest['nc'].':'.$digest['cnonce'].':'.$digest['qop'].':'.$md5);

        // Check if the string don't compare (case-insensitive)
        if (strcasecmp($digest['response'], $valid_response) !== 0)
        {
            // Display an error response
            $this->response([
                $this->config->item('rest_status_field_name') => FALSE,
                $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_invalid_credentials')
            ], self::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Checks if the client's ip is in the 'rest_ip_blacklist' config and generates a 401 response
     *
     * @access protected
     * @return void
     */
    protected function _check_blacklist_auth()
    {
        // Match an ip address in a blacklist e.g. 127.0.0.0, 0.0.0.0
        $pattern = sprintf('/(?:,\s*|^)\Q%s\E(?=,\s*|$)/m', $this->input->ip_address());

        // Returns 1, 0 or FALSE (on error only). Therefore implicitly convert 1 to TRUE
        if (preg_match($pattern, $this->config->item('rest_ip_blacklist')))
        {
            // Display an error response
            $this->response([
                $this->config->item('rest_status_field_name') => FALSE,
                $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_ip_denied')
            ], self::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Check if the client's ip is in the 'rest_ip_whitelist' config and generates a 401 response
     *
     * @access protected
     * @return void
     */
    protected function _check_whitelist_auth()
    {
        $whitelist = explode(',', $this->config->item('rest_ip_whitelist'));

        array_push($whitelist, '127.0.0.1', '0.0.0.0');

        foreach ($whitelist as &$ip)
        {
            // As $ip is a reference, trim leading and trailing whitespace, then store the new value
            // using the reference
            $ip = trim($ip);
        }

        if (in_array($this->input->ip_address(), $whitelist) === FALSE)
        {
            $this->response([
                $this->config->item('rest_status_field_name') => FALSE,
                $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_ip_unauthorized')
            ], self::HTTP_UNAUTHORIZED);
        }
    }

    /**
     * Force logging in by setting the WWW-Authenticate header
     *
     * @access protected
     * @param string $nonce A server-specified data string which should be uniquely generated
     * each time
     * @return void
     */
    protected function _force_login($nonce = '')
    {
        $rest_auth = $this->config->item('rest_auth');
        $rest_realm = $this->config->item('rest_realm');
        if (strtolower($rest_auth) === 'basic')
        {
            // See http://tools.ietf.org/html/rfc2617#page-5
            header('WWW-Authenticate: Basic realm="'.$rest_realm.'"');
        }
        elseif (strtolower($rest_auth) === 'digest')
        {
            // See http://tools.ietf.org/html/rfc2617#page-18
            header(
                'WWW-Authenticate: Digest realm="'.$rest_realm
                .'", qop="auth", nonce="'.$nonce
                .'", opaque="' . md5($rest_realm).'"');
        }

        // Display an error response
        $this->response([
            $this->config->item('rest_status_field_name') => FALSE,
            $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unauthorized')
        ], self::HTTP_UNAUTHORIZED);
    }

    /**
     * Check to see if the API key has access to the controller and methods
     *
     * @access protected
     * @return bool TRUE the API key has access; otherwise, FALSE
     */
    protected function _check_access()
    {
        // If we don't want to check access, just return TRUE
        if ($this->config->item('rest_enable_access') === FALSE)
        {
            return TRUE;
        }

        //check if the key has all_access
        $accessRow = $this->rest->db
            ->where('key', $this->rest->key)
            ->get($this->config->item('rest_access_table'))->row_array();

        if (!empty($accessRow) && !empty($accessRow['all_access']))
        {
            return TRUE;
        }

        // Fetch controller based on path and controller name
        $controller = implode(
            '/', [
            $this->router->directory,
            $this->router->class
        ]);

        // Remove any double slashes for safety
        $controller = str_replace('//', '/', $controller);

        // Query the access table and get the number of results
        return $this->rest->db
                ->where('key', $this->rest->key)
                ->where('controller', $controller)
                ->get($this->config->item('rest_access_table'))
                ->num_rows() > 0;
    }
}
