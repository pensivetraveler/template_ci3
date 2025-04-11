<?php
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP
 *
 * This content is released under the MIT License (MIT)
 *
 * Copyright (c) 2014 - 2015, British Columbia Institute of Technology
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @package    CodeIgniter
 * @author    EllisLab Dev Team
 * @copyright    Copyright (c) 2008 - 2014, EllisLab, Inc. (http://ellislab.com/)
 * @copyright    Copyright (c) 2014 - 2015, British Columbia Institute of Technology (http://bcit.ca/)
 * @license    http://opensource.org/licenses/MIT	MIT License
 * @link    http://codeigniter.com
 * @since    Version 1.0.0
 * @filesource
 */
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * Input Class
 *
 * Pre-processes global input data for security
 *
 * @package        CodeIgniter
 * @subpackage    Libraries
 * @category    Input
 * @author        EllisLab Dev Team
 * @link        http://codeigniter.com/user_guide/libraries/input.html
 */
class MY_Input extends CI_Input
{
//    /**
//     * chrome 80 cookie issue를 해결하기 위함.
//     * @param $name
//     * @param $value
//     * @param $expire
//     * @param $domain
//     * @param $path
//     * @param $prefix
//     * @param $secure
//     * @param $httponly
//     * @return void
//     */
//    public function set_cookie($name = '', $value = '', $expire = '', $domain = '', $path = '/', $prefix = '', $secure = false, $httponly = false)
//    {
//        if (PHP_VERSION_ID < 70300) {
//            // PHP 7.3 이전 버전에서는 헤더에 직접 SameSite 속성을 추가
//            $cookie = [
//                'name'     => $prefix.$name,
//                'value'    => $value,
//                'expire'   => $expire,
//                'domain'   => $domain,
//                'path'     => $path,
//                'secure'   => $secure,
//                'httponly' => $httponly,
//            ];
//            header('Set-Cookie: '.http_build_query($cookie, '', '; ').'; SameSite=None');
//        } else {
//            // PHP 7.3 이후부터는 setcookie()에서 SameSite 속성을 지원
//            $options = array(
//                'expires'  => $expire,
//                'path'     => $path,
//                'domain'   => $domain,
//                'secure'   => $secure,
//                'httponly' => $httponly,
//                'samesite' => 'None', // 혹은 'Lax', 'Strict'로 설정 가능
//            );
//            setcookie($prefix.$name, $value, $options);
//        }
//    }

    /**
     * Fetch from array
     *
     * Internal method used to retrieve values from global arrays.
     *
     * @param array    &$array $_GET, $_POST, $_COOKIE, $_SERVER, etc.
     * @param mixed $index Index for item to be fetched from $array
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return    mixed
     */
    protected function _fetch_from_array(&$array, $index = NULL, $xss_clean = NULL, $default_value = NULL)
    {
        is_bool($xss_clean) or $xss_clean = $this->_enable_xss;

        // If $index is NULL, it means that the whole $array is requested
        isset($index) or $index = array_keys($array);

        // allow fetching multiple keys at once
        if (is_array($index)) {
            $output = array();
            foreach ($index as $key) {
                $output[$key] = $this->_fetch_from_array($array, $key, $xss_clean);
            }

            return $output;
        }

        if (isset($array[$index])) {
            $value = $array[$index];
        } elseif (($count = preg_match_all('/(?:^[^\[]+)|\[[^]]*\]/', $index, $matches)) > 1) // Does the index contain array notation
        {
            $value = $array;
            for ($i = 0; $i < $count; $i++) {
                $key = trim($matches[0][$i], '[]');
                if ($key === '') // Empty notation will return the value as array
                {
                    break;
                }

                if (isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    return $default_value;
                }
            }
        } else {
            return $default_value;
        }

        return ($xss_clean === TRUE)
            ? $this->security->xss_clean($value)
            : $value;
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the GET array
     *
     * @param mixed $index Index for item to be fetched from $_GET
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return    mixed
     */
    public function get($index = NULL, $xss_clean = NULL, $default_value = NULL)
    {
        return $this->_fetch_from_array($_GET, $index, $xss_clean, $default_value);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the POST array
     *
     * @param mixed $index Index for item to be fetched from $_POST
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return    mixed
     */
    public function post($index = NULL, $xss_clean = NULL, $default_value = NULL)
    {
        return $this->_fetch_from_array($_POST, $index, $xss_clean, $default_value);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the PATCH array
     *
     * @param mixed $index Index for item to be fetched from $_PATCH
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return    mixed
     */
    public function patch($index = NULL, $xss_clean = NULL, $default_value = NULL)
    {
        if($this->method() === 'patch') {
            parse_str(file_get_contents('php://input'), $_PATCH);
            return $this->_fetch_from_array($_PATCH, $index, $xss_clean, $default_value);
        }else{
            return [];
        }
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the PUT array
     *
     * @param mixed $index Index for item to be fetched from $_PUT
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return    mixed
     */
    public function put($index = NULL, $xss_clean = NULL, $default_value = NULL)
    {
        if($this->method() === 'put') {
            parse_str(file_get_contents('php://input'), $_PUT);
            return $this->_fetch_from_array($_PUT, $index, $xss_clean, $default_value);
        }else{
            return [];
        }
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the DELETE array
     *
     * @param mixed $index Index for item to be fetched from $_DELETE
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return    mixed
     */
    public function delete($index = NULL, $xss_clean = NULL, $default_value = NULL)
    {
        if($this->method() === 'delete') {
            parse_str(file_get_contents('php://input'), $_DELETE);
            return $this->_fetch_from_array($_DELETE, $index, $xss_clean, $default_value);
        }else{
            return [];
        }
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from POST data with fallback to GET
     *
     * @param string $index Index for item to be fetched from $_POST or $_GET
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return    mixed
     */
    public function post_get($index = NULL, $xss_clean = NULL, $default_value = NULL)
    {
        return isset($_POST[$index])
            ? $this->post($index, $xss_clean, $default_value)
            : $this->get($index, $xss_clean, $default_value);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from GET data with fallback to POST
     *
     * @param string $index Index for item to be fetched from $_GET or $_POST
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return    mixed
     */
    public function get_post($index = NULL, $xss_clean = NULL, $default_value = NULL)
    {
        return isset($_GET[$index])
            ? $this->get($index, $xss_clean, $default_value)
            : $this->post($index, $xss_clean, $default_value);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from GET data with fallback to POST
     *
     * @param string $index Index for item to be fetched from $_GET or $_POST
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return    mixed
     */
    public function post_put($index = NULL, $xss_clean = NULL, $default_value = NULL)
    {
		if(is_null($index)) {
			return !empty($this->post())?$this->post():$this->put();
		}else{
			return isset($_POST[$index])
				? $this->post($index, $xss_clean, $default_value)
				: $this->put($index, $xss_clean, $default_value);
		}
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the COOKIE array
     *
     * @param mixed $index Index for item to be fetched from $_COOKIE
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return    mixed
     */
    public function cookie($index = NULL, $xss_clean = NULL, $default_value = NULL)
    {
        return $this->_fetch_from_array($_COOKIE, $index, $xss_clean, $default_value);
    }

    // --------------------------------------------------------------------

    /**
     * Fetch an item from the SERVER array
     *
     * @param mixed $index Index for item to be fetched from $_SERVER
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return    mixed
     */
    public function server($index, $xss_clean = NULL, $default_value = NULL)
    {
        return $this->_fetch_from_array($_SERVER, $index, $xss_clean, $default_value);
    }

	public function request()
	{
		return array_merge(
			$this->get(null, true),
			$this->post(null, true),
			$this->put(null, true),
			$this->patch(null, true),
			$this->delete(null, true),
		);
	}
}
