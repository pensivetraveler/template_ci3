<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH . 'traits/RestAuthTrait.php';
require_once APPPATH . 'traits/RestRequestTrait.php';
require_once APPPATH . 'traits/RestLogTrait.php';

/**
 * CodeIgniter Rest Controller
 * A fully RESTful server implementation for CodeIgniter using one library, one config file and one controller.
 *
 * @package         CodeIgniter
 * @subpackage      Libraries
 * @category        Libraries
 * @author          Phil Sturgeon, Chris Kacerguis
 * @license         MIT
 * @link            https://github.com/chriskacerguis/codeigniter-restserver
 * @version         3.0.0
 */
abstract class RestController extends \MY_Controller {
    use RestAuthTrait;
    use RestRequestTrait;
    use RestLogTrait;

    // Note: Only the widely used HTTP status codes are documented

    // Informational

    const HTTP_CONTINUE = 100;
    const HTTP_SWITCHING_PROTOCOLS = 101;
    const HTTP_PROCESSING = 102;            // RFC2518

    // Success

    /**
     * The request has succeeded
     */
    const HTTP_OK = 200;

    /**
     * The server successfully created a new resource
     */
    const HTTP_CREATED = 201;
    const HTTP_ACCEPTED = 202;
    const HTTP_NON_AUTHORITATIVE_INFORMATION = 203;

    /**
     * The server successfully processed the request, though no content is returned
     */
    const HTTP_NO_CONTENT = 204;
    const HTTP_RESET_CONTENT = 205;
    const HTTP_PARTIAL_CONTENT = 206;
    const HTTP_MULTI_STATUS = 207;          // RFC4918
    const HTTP_ALREADY_REPORTED = 208;      // RFC5842
    const HTTP_IM_USED = 226;               // RFC3229

    // Redirection

    const HTTP_MULTIPLE_CHOICES = 300;
    const HTTP_MOVED_PERMANENTLY = 301;
    const HTTP_FOUND = 302;
    const HTTP_SEE_OTHER = 303;

    /**
     * The resource has not been modified since the last request
     */
    const HTTP_NOT_MODIFIED = 304;
    const HTTP_USE_PROXY = 305;
    const HTTP_RESERVED = 306;
    const HTTP_TEMPORARY_REDIRECT = 307;
    const HTTP_PERMANENTLY_REDIRECT = 308;  // RFC7238

    // Client Error

    /**
     * The request cannot be fulfilled due to multiple errors
     */
    const HTTP_BAD_REQUEST = 400;

    /**
     * The user is unauthorized to access the requested resource
     */
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_PAYMENT_REQUIRED = 402;

    /**
     * The requested resource is unavailable at this present time
     */
    const HTTP_FORBIDDEN = 403;

    /**
     * The requested resource could not be found
     *
     * Note: This is sometimes used to mask if there was an UNAUTHORIZED (401) or
     * FORBIDDEN (403) error, for security reasons
     */
    const HTTP_NOT_FOUND = 404;

    /**
     * The request method is not supported by the following resource
     */
    const HTTP_METHOD_NOT_ALLOWED = 405;

    /**
     * The request was not acceptable
     */
    const HTTP_NOT_ACCEPTABLE = 406;
    const HTTP_PROXY_AUTHENTICATION_REQUIRED = 407;
    const HTTP_REQUEST_TIMEOUT = 408;

    /**
     * The request could not be completed due to a conflict with the current state
     * of the resource
     */
    const HTTP_CONFLICT = 409;
    const HTTP_GONE = 410;
    const HTTP_LENGTH_REQUIRED = 411;
    const HTTP_PRECONDITION_FAILED = 412;
    const HTTP_REQUEST_ENTITY_TOO_LARGE = 413;
    const HTTP_REQUEST_URI_TOO_LONG = 414;
    const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;
    const HTTP_REQUESTED_RANGE_NOT_SATISFIABLE = 416;
    const HTTP_EXPECTATION_FAILED = 417;
    const HTTP_I_AM_A_TEAPOT = 418;                                               // RFC2324
    const HTTP_UNPROCESSABLE_ENTITY = 422;                                        // RFC4918
    const HTTP_LOCKED = 423;                                                      // RFC4918
    const HTTP_FAILED_DEPENDENCY = 424;                                           // RFC4918
    const HTTP_RESERVED_FOR_WEBDAV_ADVANCED_COLLECTIONS_EXPIRED_PROPOSAL = 425;   // RFC2817
    const HTTP_UPGRADE_REQUIRED = 426;                                            // RFC2817
    const HTTP_PRECONDITION_REQUIRED = 428;                                       // RFC6585
    const HTTP_TOO_MANY_REQUESTS = 429;                                           // RFC6585
    const HTTP_REQUEST_HEADER_FIELDS_TOO_LARGE = 431;                             // RFC6585

    // Server Error

    /**
     * The server encountered an unexpected error
     *
     * Note: This is a generic error message when no specific message
     * is suitable
     */
    const HTTP_INTERNAL_SERVER_ERROR = 500;

    /**
     * The server does not recognise the request method
     */
    const HTTP_NOT_IMPLEMENTED = 501;
    const HTTP_BAD_GATEWAY = 502;
    const HTTP_SERVICE_UNAVAILABLE = 503;
    const HTTP_GATEWAY_TIMEOUT = 504;
    const HTTP_VERSION_NOT_SUPPORTED = 505;
    const HTTP_VARIANT_ALSO_NEGOTIATES_EXPERIMENTAL = 506;                        // RFC2295
    const HTTP_INSUFFICIENT_STORAGE = 507;                                        // RFC4918
    const HTTP_LOOP_DETECTED = 508;                                               // RFC5842
    const HTTP_NOT_EXTENDED = 510;                                                // RFC2774
    const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;

    /**
     * This defines the rest format
     * Must be overridden it in a controller so that it is set
     *
     * @var string|NULL
     */
    protected $rest_format = NULL;

    /**
     * Defines the list of method properties such as limit, log and level
     *
     * @var array
     */
    protected $methods = [];

    /**
     * List of allowed HTTP methods
     *
     * @var array
     */
    protected $allowed_http_methods = ['get', 'delete', 'post', 'put', 'options', 'patch', 'head'];

    /**
     * Contains details about the request
     * Fields: body, format, method, ssl
     * Note: This is a dynamic object (stdClass)
     *
     * @var object
     */
    protected $request = NULL;

    /**
     * Contains details about the response
     * Fields: format, lang
     * Note: This is a dynamic object (stdClass)
     *
     * @var object
     */
    protected $response = NULL;

    /**
     * Contains details about the REST API
     * Fields: db, ignore_limits, key, level, user_id
     * Note: This is a dynamic object (stdClass)
     *
     * @var object
     */
    protected $rest = NULL;

    /**
     * The arguments for the GET request method
     *
     * @var array
     */
    protected $_get_args = [];

    /**
     * The arguments for the POST request method
     *
     * @var array
     */
    protected $_post_args = [];

    /**
     * The arguments for the PUT request method
     *
     * @var array
     */
    protected $_put_args = [];

    /**
     * The arguments for the DELETE request method
     *
     * @var array
     */
    protected $_delete_args = [];

    /**
     * The arguments for the PATCH request method
     *
     * @var array
     */
    protected $_patch_args = [];

    /**
     * The arguments for the HEAD request method
     *
     * @var array
     */
    protected $_head_args = [];

    /**
     * The arguments for the OPTIONS request method
     *
     * @var array
     */
    protected $_options_args = [];

    /**
     * The arguments for the query parameters
     *
     * @var array
     */
    protected $_query_args = [];

    /**
     * The arguments from GET, POST, PUT, DELETE, PATCH, HEAD and OPTIONS request methods combined
     *
     * @var array
     */
    protected $_args = [];

    /**
     * The insert_id of the log entry (if we have one)
     *
     * @var string
     */
    protected $_insert_id = '';

    /**
     * If the request is allowed based on the API key provided
     *
     * @var bool
     */
    protected $_allow = TRUE;

    /**
     * The LDAP Distinguished Name of the User post authentication
     *
     * @var string
     */
    protected $_user_ldap_dn = '';

    /**
     * The start of the response time from the server
     *
     * @var number
     */
    protected $_start_rtime;

    /**
     * The end of the response time from the server
     *
     * @var number
     */
    protected $_end_rtime;

    /**
     * List all supported methods, the first will be the default format
     *
     * @var array
     */
    protected $_supported_formats = [
        'json' => 'application/json',
        'array' => 'application/json',
        'csv' => 'application/csv',
        'html' => 'text/html',
        'jsonp' => 'application/javascript',
        'php' => 'text/plain',
        'serialized' => 'application/vnd.php.serialized',
        'xml' => 'application/xml',
        'multipart' => 'multipart/form-data',
    ];

    /**
     * Information about the current API user
     *
     * @var object
     */
    protected $_apiuser;

    /**
     * Whether or not to perform a CORS check and apply CORS headers to the request
     *
     * @var bool
     */
    protected $check_cors = NULL;

    /**
     * Enable XSS flag
     * Determines whether the XSS filter is always active when
     * GET, OPTIONS, HEAD, POST, PUT, DELETE and PATCH data is encountered
     * Set automatically based on config setting
     *
     * @var bool
     */
    protected $_enable_xss = FALSE;

    /**
     * HTTP status codes and their respective description
     * Note: Only the widely used HTTP status codes are used
     *
     * @var array
     * @link http://www.restapitutorial.com/httpstatuscodes.html
     */
    protected $http_status_codes = [
        self::HTTP_OK => 'OK',
        self::HTTP_CREATED => 'CREATED',
        self::HTTP_NO_CONTENT => 'NO CONTENT',
        self::HTTP_NOT_MODIFIED => 'NOT MODIFIED',
        self::HTTP_BAD_REQUEST => 'BAD REQUEST',
        self::HTTP_UNAUTHORIZED => 'UNAUTHORIZED',
        self::HTTP_FORBIDDEN => 'FORBIDDEN',
        self::HTTP_NOT_FOUND => 'NOT FOUND',
        self::HTTP_METHOD_NOT_ALLOWED => 'METHOD NOT ALLOWED',
        self::HTTP_NOT_ACCEPTABLE => 'NOT ACCEPTABLE',
        self::HTTP_CONFLICT => 'CONFLICT',
        self::HTTP_INTERNAL_SERVER_ERROR => 'INTERNAL SERVER ERROR',
        self::HTTP_NOT_IMPLEMENTED => 'NOT IMPLEMENTED'
    ];

    /**
     * Extend this function to apply additional checking early on in the process
     *
     * @access protected
     * @return void
     */
    protected function early_checks()
    {
    }

    /**
     * Constructor for the REST API
     *
     * @access public
     * @param string $config Configuration filename minus the file extension
     * e.g: my_rest.php is passed as 'my_rest'
     */
    public function __construct($config = 'rest')
    {
        parent::__construct();

        $this->preflight_checks();

        // Set the default value of global xss filtering. Same approach as CodeIgniter 3
        $this->_enable_xss = ($this->config->item('global_xss_filtering') === TRUE);

        // Don't try to parse template variables like {elapsed_time} and {memory_usage}
        // when output is displayed for not damaging data accidentally
        $this->output->parse_exec_vars = FALSE;

        // Start the timer for how long the request takes
        $this->_start_rtime = microtime(TRUE);

        // Load the rest.php configuration file
        $this->load->config($config);

        // At present the library is bundled with REST_Controller 2.5+, but will eventually be part of CodeIgniter (no citation)
        $this->load->library('format');

        // Determine supported output formats from configuration
        $supported_formats = $this->config->item('rest_supported_formats');

        // Validate the configuration setting output formats
        if (empty($supported_formats))
        {
            $supported_formats = [];
        }

        if ( ! is_array($supported_formats))
        {
            $supported_formats = [$supported_formats];
        }

        // Add silently the default output format if it is missing
        $default_format = $this->_get_default_output_format();
        if (!in_array($default_format, $supported_formats))
        {
            $supported_formats[] = $default_format;
        }

        // Now update $this->_supported_formats
        $this->_supported_formats = array_intersect_key($this->_supported_formats, array_flip($supported_formats));

        // Get the language
        $language = $this->config->item('rest_language');
        if ($language === NULL)
        {
            $language = 'english';
        }

        // Load the language file
        $this->lang->load('rest_controller', $language, FALSE, TRUE, __DIR__."/../");

        // Initialise the response, request and rest objects
        $this->request = new stdClass();
        $this->response = new stdClass();
        $this->rest = new stdClass();

        // Check to see if the current IP address is blacklisted
        if ($this->config->item('rest_ip_blacklist_enabled') === TRUE)
        {
            $this->_check_blacklist_auth();
        }

        // Determine whether the connection is HTTPS
        $this->request->ssl = is_https();

        // How is this request being made? GET, POST, PATCH, DELETE, INSERT, PUT, HEAD or OPTIONS
        $this->request->method = $this->_detect_method();

        // Check for CORS access request
        $check_cors = $this->config->item('check_cors');
        if ($check_cors === TRUE)
        {
            $this->_check_cors();
        }

        // Create an argument container if it doesn't exist e.g. _get_args
        if (isset($this->{'_'.$this->request->method.'_args'}) === FALSE)
        {
            $this->{'_'.$this->request->method.'_args'} = [];
        }

        // Set up the query parameters
        $this->_parse_query();

        // Set up the GET variables
        $this->_get_args = array_merge($this->_get_args, $this->uri->ruri_to_assoc());

        // Try to find a format for the request (means we have a request body)
        $this->request->format = $this->_detect_input_format();

        // Not all methods have a body attached with them
        $this->request->body = NULL;

        $this->{'_parse_' . $this->request->method}();

        // Fix parse method return arguments null
        if($this->{'_'.$this->request->method.'_args'} === null)
        {
            $this->{'_'.$this->request->method.'_args'} = [];
        }

        // Now we know all about our request, let's try and parse the body if it exists
        if ($this->request->format && $this->request->body)
        {
            $this->request->body = $this->format->factory($this->request->body, $this->request->format)->to_array();
            // Assign payload arguments to proper method container
            $this->{'_'.$this->request->method.'_args'} = $this->request->body;
        }

        //get header vars
        $this->_head_args = $this->input->request_headers();

        // Merge both for one mega-args variable
        $this->_args = array_merge(
            $this->_get_args,
            $this->_options_args,
            $this->_patch_args,
            $this->_head_args,
            $this->_put_args,
            $this->_post_args,
            $this->_delete_args,
            $this->{'_'.$this->request->method.'_args'}
        );

        // Which format should the data be returned in?
        $this->response->format = $this->_detect_output_format();

        // Which language should the data be returned in?
        $this->response->lang = $this->_detect_lang();

        // Extend this function to apply additional checking early on in the process
        $this->early_checks();

        // Load DB if its enabled
        if ($this->config->item('rest_database_group') && ($this->config->item('rest_enable_keys') || $this->config->item('rest_enable_logging')))
        {
            $this->rest->db = $this->load->database($this->config->item('rest_database_group'), TRUE);
        }

        // Use whatever database is in use (isset returns FALSE)
        elseif (property_exists($this, 'db'))
        {
            $this->rest->db = $this->db;
        }

        // Check if there is a specific auth type for the current class/method
        // _auth_override_check could exit so we need $this->rest->db initialized before
        $this->auth_override = $this->_auth_override_check();

        // Checking for keys? GET TO WorK!
        // Skip keys test for $config['auth_override_class_method']['class'['method'] = 'none'
        if ($this->config->item('rest_enable_keys') && $this->auth_override !== TRUE)
        {
            $this->_allow = $this->_detect_api_key();
        }

        // Only allow ajax requests
        if ($this->input->is_ajax_request() === FALSE && $this->config->item('rest_ajax_only'))
        {
            // Display an error response
            $this->response([
                $this->config->item('rest_status_field_name') => FALSE,
                $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_ajax_only')
            ], self::HTTP_NOT_ACCEPTABLE);
        }

        // When there is no specific override for the current class/method, use the default auth value set in the config
        if ($this->auth_override === FALSE &&
            (! ($this->config->item('rest_enable_keys') && $this->_allow === TRUE) ||
                ($this->config->item('allow_auth_and_keys') === TRUE && $this->_allow === TRUE)))
        {
            $rest_auth = strtolower($this->config->item('rest_auth'));
            switch ($rest_auth)
            {
                case 'basic':
                    $this->_prepare_basic_auth();
                    break;
                case 'digest':
                    $this->_prepare_digest_auth();
                    break;
                case 'session':
                    $this->_check_php_session();
                    break;
            }
            if ($this->config->item('rest_ip_whitelist_enabled') === TRUE)
            {
                $this->_check_whitelist_auth();
            }
        }
    }

    /**
     * De-constructor
     *
     * @author Chris Kacerguis
     * @access public
     * @return void
     */
    public function __destruct()
    {
        // Get the current timestamp
        $this->_end_rtime = microtime(TRUE);

        // Log the loading time to the log table
        if (
            $this->config->item('rest_enable_logging') === TRUE &&
            !empty($this->_insert_id) &&
            isset($this->rest) &&
            isset($this->rest->db)
        ) {
            $this->_log_access_time();
        }
    }

    /**
     * Checks to see if we have everything we need to run this library.
     *
     * @access protected
     * @throws Exception
     */
    protected function preflight_checks()
    {
        // Check to see if PHP is equal to or greater than 5.4.x
        if (is_php('5.4') === FALSE)
        {
            // CodeIgniter 3 is recommended for v5.4 or above
            throw new Exception('Using PHP v'.PHP_VERSION.', though PHP v5.4 or greater is required');
        }

        // Check to see if this is CI 3.x
        if (explode('.', CI_VERSION, 2)[0] < 3)
        {
            throw new Exception('REST Server requires CodeIgniter 3.x');
        }
    }

    /**
     * Requests are not made to methods directly, the request will be for
     * an "object". This simply maps the object and method to the correct
     * Controller method
     *
     * @access public
     * @param string $object_called
     * @param array $arguments The arguments passed to the controller method
     */
    public function _remap($object_called, $arguments = [])
    {
        // Should we answer if not over SSL?
        if ($this->config->item('force_https') && $this->request->ssl === FALSE)
        {
            $this->response([
                $this->config->item('rest_status_field_name') => FALSE,
                $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unsupported')
            ], self::HTTP_FORBIDDEN);
        }

        // Remove the supported format from the function name e.g. index.json => index
        $object_called = preg_replace('/^(.*)\.(?:'.implode('|', array_keys($this->_supported_formats)).')$/', '$1', $object_called);

        $controller_method = $object_called.'_'.$this->request->method;
        // Does this method exist? If not, try executing an index method
        if (!method_exists($this, $controller_method)) {
            $controller_method = "index_" . $this->request->method;
            array_unshift($arguments, $object_called);
        }

        // Do we want to log this method (if allowed by config)?
        $log_method = ! (isset($this->methods[$controller_method]['log']) && $this->methods[$controller_method]['log'] === FALSE);

        // Use keys for this method?
        $use_key = ! (isset($this->methods[$controller_method]['key']) && $this->methods[$controller_method]['key'] === FALSE);

        // They provided a key, but it wasn't valid, so get them out of here
        if ($this->config->item('rest_enable_keys') && $use_key && $this->_allow === FALSE)
        {
            if ($this->config->item('rest_enable_logging') && $log_method)
            {
                $this->_log_request();
            }

            // fix cross site to option request error
            if($this->request->method == 'options') {
                exit;
            }

            if(empty($this->rest->key)) {
                $this->response([
                    $this->config->item('rest_status_field_name') => FALSE,
                    $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_empty_api_key')
                ], self::HTTP_FORBIDDEN);
            }else{
                $this->response([
                    $this->config->item('rest_status_field_name') => FALSE,
                    $this->config->item('rest_message_field_name') => sprintf($this->lang->line('text_rest_invalid_api_key'), $this->rest->key)
                ], self::HTTP_FORBIDDEN);
            }
        }

        // Check to see if this key has access to the requested controller
        if ($this->config->item('rest_enable_keys') && $use_key && empty($this->rest->key) === FALSE && $this->_check_access() === FALSE)
        {
            if ($this->config->item('rest_enable_logging') && $log_method)
            {
                $this->_log_request();
            }

            $this->response([
                $this->config->item('rest_status_field_name') => FALSE,
                $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_api_key_unauthorized')
            ], self::HTTP_UNAUTHORIZED);
        }

        // Sure it exists, but can they do anything with it?
        if (! method_exists($this, $controller_method))
        {
            $this->response([
                $this->config->item('rest_status_field_name') => FALSE,
                $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_unknown_method')
            ], self::HTTP_METHOD_NOT_ALLOWED);
        }

        // Doing key related stuff? Can only do it if they have a key right?
        if ($this->config->item('rest_enable_keys') && empty($this->rest->key) === FALSE)
        {
            // Check the limit
            if ($this->config->item('rest_enable_limits') && $this->_check_limit($controller_method) === FALSE)
            {
                $response = [$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_api_key_time_limit')];
                $this->response($response, self::HTTP_UNAUTHORIZED);
            }

            // If no level is set use 0, they probably aren't using permissions
            $level = isset($this->methods[$controller_method]['level']) ? $this->methods[$controller_method]['level'] : 0;

            // If no level is set, or it is lower than/equal to the key's level
            $authorized = $level <= $this->rest->level;
            // IM TELLIN!
            if ($this->config->item('rest_enable_logging') && $log_method)
            {
                $this->_log_request($authorized);
            }
            if($authorized === FALSE)
            {
                // They don't have good enough perms
                $response = [$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_api_key_permissions')];
                $this->response($response, self::HTTP_UNAUTHORIZED);
            }
        }

        //check request limit by ip without login
        // API 키 없이 부르는 엔드포인트(로그인 등)도 limit 이 정해져 있으면 IP 별로 센다 (전에는 IP_ADDRESS 방식일 때만 검사)
        elseif (($this->config->item('rest_limits_method') == "IP_ADDRESS" || isset($this->methods[$controller_method]['limit'])) && $this->config->item('rest_enable_limits') && $this->_check_limit($controller_method) === FALSE)
        {
            $response = [$this->config->item('rest_status_field_name') => FALSE, $this->config->item('rest_message_field_name') => $this->lang->line('text_rest_ip_address_time_limit')];
            $this->response($response, self::HTTP_UNAUTHORIZED);
        }

        // No key stuff, but record that stuff is happening
        elseif ($this->config->item('rest_enable_logging') && $log_method)
        {
            $this->_log_request($authorized = TRUE);
        }

        // 인증·요청 제한·요청 로그를 마친 뒤, 컨트롤러 메서드 직전 (MY_Builder_API 가 요청 트랜잭션을 여기서 연다 —
        // 로그·제한 카운터가 트랜잭션 안에 들어가 실패 응답 때 함께 롤백되지 않도록)
        $this->beforeControllerMethod($controller_method);

        // Call the controller method and passed arguments
        try
        {
            call_user_func_array([$this, $controller_method], $arguments);
        }
        catch (Exception $ex)
        {
            if ($this->config->item('rest_handle_exceptions') === FALSE) {
                throw $ex;
            }

            // If the method doesn't exist, then the error will be caught and an error response shown
            $_error = &load_class('Exceptions', 'core');
            $_error->show_exception($ex);
        }
    }

    /**
     * 컨트롤러 메서드를 부르기 직전 (하위 클래스에서 오버라이드)
     */
    protected function beforeControllerMethod(string $controller_method): void
    {
    }

    /**
     * Takes mixed data and optionally a status code, then creates the response
     *
     * @access public
     * @param array|NULL $data Data to output to the user
     * @param int|NULL $http_code HTTP status code
     * running the script; otherwise, exit
     */
    public function response($data = NULL, $http_code = NULL)
    {
        ob_start();
        // If the HTTP status is not NULL, then cast as an integer
        if ($http_code !== NULL)
        {
            // So as to be safe later on in the process
            $http_code = (int) $http_code;
        }

        // Set the output as NULL by default
        $output = NULL;

        // If data is NULL and no HTTP status code provided, then display, error and exit
        if ($data === NULL && $http_code === NULL)
        {
            $http_code = self::HTTP_NOT_FOUND;
        }

        // If data is not NULL and a HTTP status code provided, then continue
        elseif ($data !== NULL)
        {
            // If the format method exists, call and return the output in that format
            if (method_exists($this->format, 'to_' . $this->response->format))
            {
                // Set the format header
                $this->output->set_content_type($this->_supported_formats[$this->response->format], strtolower($this->config->item('charset')));
                $output = $this->format->factory($data)->{'to_' . $this->response->format}();

                // An array must be parsed as a string, so as not to cause an array to string error
                // Json is the most appropriate form for such a data type
                if ($this->response->format === 'array')
                {
                    $output = $this->format->factory($output)->{'to_json'}();
                }
            }
            else
            {
                // If an array or object, then parse as a json, so as to be a 'string'
                if (is_array($data) || is_object($data))
                {
                    $data = $this->format->factory($data)->{'to_json'}();
                }

                // Format is not supported, so output the raw data as a string
                $output = $data;
            }
        }

        // If not greater than zero, then set the HTTP status code as 200 by default
        // Though perhaps 500 should be set instead, for the developer not passing a
        // correct HTTP status code
        $http_code > 0 || $http_code = self::HTTP_OK;

        $this->output->set_status_header($http_code);

        // JC: Log response code only if rest logging enabled
        if ($this->config->item('rest_enable_logging') === TRUE)
        {
            $this->_log_response_code($http_code);
        }

        // Output the data
        $this->output->set_output($output);

        ob_end_flush();

        // Otherwise dump the output automatically
    }

    /**
     * Takes mixed data and optionally a status code, then creates the response
     * within the buffers of the Output class. The response is sent to the client
     * lately by the framework, after the current controller's method termination.
     * All the hooks after the controller's method termination are executable
     *
     * @access public
     * @param array|NULL $data Data to output to the user
     * @param int|NULL $http_code HTTP status code
     */
    public function set_response($data = NULL, $http_code = NULL)
    {
        $this->response($data, $http_code, TRUE);
    }

    /**
     * Get the input format e.g. json or xml
     *
     * @access protected
     * @return string|NULL Supported input format; otherwise, NULL
     */
    protected function _detect_input_format()
    {
        // Get the CONTENT-TYPE value from the SERVER variable
        $content_type = $this->input->server('CONTENT_TYPE');

        if (empty($content_type) === FALSE)
        {
            // If a semi-colon exists in the string, then explode by ; and get the value of where
            // the current array pointer resides. This will generally be the first element of the array
            $content_type = (strpos($content_type, ';') !== FALSE ? current(explode(';', $content_type)) : $content_type);

            // Check all formats against the CONTENT-TYPE header
            foreach ($this->_supported_formats as $type => $mime)
            {
                // $type = format e.g. csv
                // $mime = mime type e.g. application/csv

                // If both the mime types match, then return the format
                if ($content_type === $mime)
                {
                    return $type;
                }
            }
        }

        return NULL;
    }

    /**
     * Gets the default format from the configuration. Fallbacks to 'json'
     * if the corresponding configuration option $config['rest_default_format']
     * is missing or is empty
     *
     * @access protected
     * @return string The default supported input format
     */
    protected function _get_default_output_format()
    {
        $default_format = (string) $this->config->item('rest_default_format');
        return $default_format === '' ? 'json' : $default_format;
    }

    /**
     * Detect which format should be used to output the data
     *
     * @access protected
     * @return mixed|NULL|string Output format
     */
    protected function _detect_output_format()
    {
        // Concatenate formats to a regex pattern e.g. \.(csv|json|xml)
        $pattern = '/\.('.implode('|', array_keys($this->_supported_formats)).')($|\/)/';
        $matches = [];

        // Check if a file extension is used e.g. http://example.com/api/index.json?param1=param2
        if (preg_match($pattern, $this->uri->uri_string(), $matches))
        {
            return $matches[1];
        }

        // Get the format parameter named as 'format'
        if (isset($this->_get_args['format']))
        {
            $format = strtolower($this->_get_args['format']);

            if (isset($this->_supported_formats[$format]) === TRUE)
            {
                return $format;
            }
        }

        // Get the HTTP_ACCEPT server variable
        $http_accept = $this->input->server('HTTP_ACCEPT');

        // Otherwise, check the HTTP_ACCEPT server variable
        if ($this->config->item('rest_ignore_http_accept') === FALSE && $http_accept !== NULL)
        {
            // Check all formats against the HTTP_ACCEPT header
            foreach (array_keys($this->_supported_formats) as $format)
            {
                // Has this format been requested?
                if (strpos($http_accept, $format) !== FALSE)
                {
                    if ($format !== 'html' && $format !== 'xml')
                    {
                        // If not HTML or XML assume it's correct
                        return $format;
                    }
                    elseif ($format === 'html' && strpos($http_accept, 'xml') === FALSE)
                    {
                        // HTML or XML have shown up as a match
                        // If it is truly HTML, it wont want any XML
                        return $format;
                    }
                    else if ($format === 'xml' && strpos($http_accept, 'html') === FALSE)
                    {
                        // If it is truly XML, it wont want any HTML
                        return $format;
                    }
                }
            }
        }

        // Check if the controller has a default format
        if (empty($this->rest_format) === FALSE)
        {
            return $this->rest_format;
        }

        // Obtain the default format from the configuration
        return $this->_get_default_output_format();
    }

    /**
     * Get the HTTP request string e.g. get or post
     *
     * @access protected
     * @return string|NULL Supported request method as a lowercase string; otherwise, NULL if not supported
     */
    protected function _detect_method()
    {
        // Declare a variable to store the method
        $method = NULL;

        // Determine whether the 'enable_emulate_request' setting is enabled
        if ($this->config->item('enable_emulate_request') === TRUE)
        {
            $method = $this->input->post('_method');
            if ($method === NULL)
            {
                $method = $this->input->server('HTTP_X_HTTP_METHOD_OVERRIDE');
            }

            $method = strtolower($method);
        }

        if (empty($method))
        {
            // Get the request method as a lowercase string
            $method = $this->input->method();
        }

        return in_array($method, $this->allowed_http_methods) && method_exists($this, '_parse_' . $method) ? $method : 'get';
    }

    /**
     * See if the user has provided an API key
     *
     * @access protected
     * @return bool
     */
    protected function _detect_api_key()
    {
        // Get the api key name variable set in the rest config file
        $api_key_variable = $this->config->item('rest_key_name');

        // Work out the name of the SERVER entry based on config
        $key_name = 'HTTP_' . strtoupper(str_replace('-', '_', $api_key_variable));

        $this->rest->key = NULL;
        $this->rest->level = NULL;
        $this->rest->user_id = NULL;
        $this->rest->ignore_limits = FALSE;

        // Find the key from server or arguments
        if ($key = $this->_args[$api_key_variable] ?? $this->input->server($key_name))
        {
            if ($this->config->item('rest_key_bearer') === TRUE && $api_key_variable === 'Authorization') {
                if (!preg_match('/^Bearer\s+(.+)$/i', $key, $matches)) {
                    return FALSE;
                }

                $key = trim($matches[1]);

                if ($key === '') {
                    return FALSE;
                }
            }

            if ( ! ($row = $this->rest->db->where($this->config->item('rest_key_column'), $key)->get($this->config->item('rest_keys_table'))->row()))
            {
                return FALSE;
            }

            // 발급 후 token_lifetime() 이 지난 토큰은 받지 않는다 (common_helper is_token_expired, 0 이면 만료 없음)
            if (property_exists($row, 'created_dt') && function_exists('is_token_expired') && is_token_expired($row->created_dt))
            {
                return FALSE;
            }

            $this->rest->key = $row->{$this->config->item('rest_key_column')};

            isset($row->user_id) && $this->rest->user_id = $row->user_id;
            isset($row->level) && $this->rest->level = $row->level;
            isset($row->ignore_limits) && $this->rest->ignore_limits = $row->ignore_limits;

            $this->_apiuser = $row;

            /*
             * If "is private key" is enabled, compare the ip address with the list
             * of valid ip addresses stored in the database
             */
            if (empty($row->is_private_key) === FALSE)
            {
                // Check for a list of valid ip addresses
                if (isset($row->ip_addresses))
                {
                    // multiple ip addresses must be separated using a comma, explode and loop
                    $list_ip_addresses = explode(',', $row->ip_addresses);
                    $found_address = FALSE;

                    foreach ($list_ip_addresses as $ip_address)
                    {
                        if ($this->input->ip_address() === trim($ip_address))
                        {
                            // there is a match, set the the value to TRUE and break out of the loop
                            $found_address = TRUE;
                            break;
                        }
                    }

                    return $found_address;
                }
                else
                {
                    // There should be at least one IP address for this private key
                    return FALSE;
                }
            }

            return TRUE;
        }

        // No key has been sent
        return FALSE;
    }

    /**
     * Preferred return language
     *
     * @access protected
     * @return string|NULL The language code
     */
    protected function _detect_lang()
    {
        $lang = $this->input->server('HTTP_ACCEPT_LANGUAGE');
        if ($lang === NULL)
        {
            return NULL;
        }

        // It appears more than one language has been sent using a comma delimiter
        if (strpos($lang, ',') !== FALSE)
        {
            $langs = explode(',', $lang);

            $return_langs = [];
            foreach ($langs as $lang)
            {
                // Remove weight and trim leading and trailing whitespace
                list($lang) = explode(';', $lang);
                $return_langs[] = trim($lang);
            }

            return $return_langs;
        }

        // Otherwise simply return as a string
        return $lang;
    }

    /**
     * Check if the requests to a controller method exceed a limit
     *
     * @access protected
     * @param string $controller_method The method being called
     * @return bool TRUE the call limit is below the threshold; otherwise, FALSE
     */
    protected function _check_limit($controller_method)
    {
        // They are special, or it might not even have a limit
        // 로그인 토큰은 ignore_limits 라 제한을 건너뛴다. limit_always 인 메서드는 그래도 센다
        if (empty($this->rest->ignore_limits) === FALSE && empty($this->methods[$controller_method]['limit_always']))
        {
            // Everything is fine
            return TRUE;
        }

        $api_key = isset($this->rest->key) ? $this->rest->key : '';

        switch ($this->config->item('rest_limits_method'))
        {
            case 'IP_ADDRESS':
                $limited_uri = 'ip-address:' .$this->input->ip_address();
                $api_key = $this->input->ip_address();
                break;

            case 'API_KEY':
                $limited_uri = 'api-key:' . $api_key;
                break;

            case 'METHOD_NAME':
                $limited_uri = 'method-name:' . $controller_method;
                break;

            case 'ROUTED_URL':
            default:
                $limited_uri = $this->uri->ruri_string();
                if (strpos(strrev($limited_uri), strrev($this->response->format)) === 0)
                {
                    $limited_uri = substr($limited_uri,0, -strlen($this->response->format) - 1);
                }
                $limited_uri = 'uri:'.$limited_uri.':'.$this->request->method; // It's good to differentiate GET from PUT
                break;
        }

        // API 키가 없으면 IP 별로 센다 (빈 키로 세면 모든 사용자가 한 카운터를 같이 써서, 로그인 제한이 사이트 전체를 막음)
        if ($api_key === '' || $api_key === NULL)
        {
            $api_key = $this->input->ip_address();
        }

        if (isset($this->methods[$controller_method]['limit']) === FALSE )
        {
            // Everything is fine
            return TRUE;
        }

        // 제한 표 구조가 다르면(uri·api_key 컬럼 없음) 요청 전체가 500 이 되므로, 제한만 건너뛰고 오류 로그를 남긴다
        // 맞추는 SQL: docs/migrations/2026-10-27-rest-user-limits.sql
        static $limitsTableReady = NULL;
        if ($limitsTableReady === NULL)
        {
            $table = $this->config->item('rest_limits_table');
            $limitsTableReady = $this->rest->db->table_exists($table)
                && $this->rest->db->field_exists('uri', $table)
                && $this->rest->db->field_exists('api_key', $table);
            if ( ! $limitsTableReady)
            {
                log_message('error', __METHOD__.' : rest limits table "'.$table.'" needs uri·api_key columns — limit skipped. See docs/migrations/2026-10-27-rest-user-limits.sql');
            }
        }
        if ( ! $limitsTableReady)
        {
            return TRUE;
        }

        // How many times can you get to this method in a defined time_limit (default: 1 hour)?
        $limit = $this->methods[$controller_method]['limit'];

        $time_limit = (isset($this->methods[$controller_method]['time']) ? $this->methods[$controller_method]['time'] : 3600); // 3600 = 60 * 60

        // Get data about a keys' usage and limit to one row
        $result = $this->rest->db
            ->where('uri', $limited_uri)
            ->where('api_key', $api_key)
            ->get($this->config->item('rest_limits_table'))
            ->row();

        // No calls have been made for this key
        if ($result === NULL)
        {
            // Create a new row for the following key
            $this->rest->db->insert($this->config->item('rest_limits_table'), [
                'uri' => $limited_uri,
                'api_key' =>$api_key,
                'count' => 1,
                'hour_started' => time()
            ]);
        }

        // Been a time limit (or by default an hour) since they called
        elseif ($result->hour_started < (time() - $time_limit))
        {
            // Reset the started period and count
            $this->rest->db
                ->where('uri', $limited_uri)
                ->where('api_key', $api_key)
                ->set('hour_started', time())
                ->set('count', 1)
                ->update($this->config->item('rest_limits_table'));
        }

        // They have called within the hour, so lets update
        else
        {
            // The limit has been exceeded
            if ($result->count >= $limit)
            {
                return FALSE;
            }

            // Increase the count by one
            $this->rest->db
                ->where('uri', $limited_uri)
                ->where('api_key', $api_key)
                ->set('count', 'count + 1', FALSE)
                ->update($this->config->item('rest_limits_table'));
        }

        return TRUE;
    }

    // INPUT FUNCTION --------------------------------------------------------------

    // SECURITY FUNCTIONS ---------------------------------------------------------

    /**
     * Checks allowed domains, and adds appropriate headers for HTTP access control (CORS)
     *
     * @access protected
     * @return void
     */
    protected function _check_cors()
    {
        // Convert the config items into strings
        $allowed_headers = $this->config->item('allowed_cors_headers');
        $allowed_methods = $this->config->item('allowed_cors_methods');

        if (!is_array($allowed_headers)) {
            $allowed_headers = [];
        }

        if (!is_array($allowed_methods)) {
            $allowed_methods = [];
        }

        // Automatically include the configured API key header in the allowed CORS headers.
        $rest_key_name = $this->config->item('rest_key_name');

        if (!empty($rest_key_name) && !in_array($rest_key_name, $allowed_headers, true)) {
            $allowed_headers[] = $rest_key_name;
        }

        // Include headers commonly used by CORS preflight requests.
        foreach (['Access-Control-Request-Method', 'Access-Control-Request-Headers'] as $header) {
            if (!in_array($header, $allowed_headers, true)) {
                $allowed_headers[] = $header;
            }
        }

        // Remove duplicate values and convert the arrays into header-safe strings.
        $allowed_headers = implode(', ', array_unique($allowed_headers));
        $allowed_methods = implode(', ', array_unique($allowed_methods));

        if ($this->config->item('allow_any_cors_domain') === true) {
            // Allow requests from any origin.
            header('Access-Control-Allow-Origin: *');
        } else {
            $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

            $allowed_origins = $this->config->item('allowed_cors_origins');

            // Allow the request origin only when it is explicitly listed.
            if (is_array($allowed_origins) && in_array($origin, $allowed_origins, true)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Vary: Origin');
            }
        }

        // Send the allowed headers and methods for CORS.
        header('Access-Control-Allow-Headers: ' . $allowed_headers);
        header('Access-Control-Allow-Methods: ' . $allowed_methods);

        // Stop here for preflight requests.
        // The actual controller method should not be executed for OPTIONS requests.
        if ($this->input->method() === 'options') {
            exit;
        }
    }
}
