<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * RestController 요청 값: 메서드별 인자 읽기와 XSS 정리
 */
trait RestRequestTrait
{
    /**
     * Parse the GET request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_get()
    {
        // Merge both the URI segments and query parameters
        $this->_get_args = array_merge($this->_get_args, $this->_query_args);
    }

    /**
     * Parse the POST request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_post()
    {
        $this->_post_args = $_POST;

        if ($this->request->format)
        {
            $this->request->body = $this->input->raw_input_stream;
        }
    }

    /**
     * Parse the PUT request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_put()
    {
        if ($this->request->format)
        {
            $this->request->body = $this->input->raw_input_stream;
            if ($this->request->format === 'json')
            {
                $this->_put_args = json_decode($this->input->raw_input_stream);
            }
        }
        else if ($this->input->method() === 'put')
        {
            // If no file type is provided, then there are probably just arguments
            $this->_put_args = $this->input->input_stream();
        }
    }

    /**
     * Parse the HEAD request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_head()
    {
        // Parse the HEAD variables
        parse_str(parse_url($this->input->server('REQUEST_URI'), PHP_URL_QUERY), $head);

        // Merge both the URI segments and HEAD params
        $this->_head_args = array_merge($this->_head_args, $head);
    }

    /**
     * Parse the OPTIONS request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_options()
    {
        // Parse the OPTIONS variables
        parse_str(parse_url($this->input->server('REQUEST_URI'), PHP_URL_QUERY), $options);

        // Merge both the URI segments and OPTIONS params
        $this->_options_args = array_merge($this->_options_args, $options);
    }

    /**
     * Parse the PATCH request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_patch()
    {
        // It might be a HTTP body
        if ($this->request->format)
        {
            $this->request->body = $this->input->raw_input_stream;
        }
        else if ($this->input->method() === 'patch')
        {
            // If no file type is provided, then there are probably just arguments
            $this->_patch_args = $this->input->input_stream();
        }
    }

    /**
     * Parse the DELETE request arguments
     *
     * @access protected
     * @return void
     */
    protected function _parse_delete()
    {
        // These should exist if a DELETE request
        if ($this->input->method() === 'delete')
        {
            $this->_delete_args = $this->input->input_stream();
        }
    }

    /**
     * Parse the query parameters
     *
     * @access protected
     * @return void
     */
    protected function _parse_query()
    {
        $this->_query_args = $this->input->get();
    }

    /**
     * Retrieve a value from a GET request
     *
     * @access public
     * @param NULL $key Key to retrieve from the GET request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the GET request; otherwise, NULL
     */
    public function get($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_get_args;
        }

        return isset($this->_get_args[$key]) ? $this->_xss_clean($this->_get_args[$key], $xss_clean) : NULL;
    }

    /**
     * Retrieve a value from a OPTIONS request
     *
     * @access public
     * @param NULL $key Key to retrieve from the OPTIONS request.
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the OPTIONS request; otherwise, NULL
     */
    public function options($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_options_args;
        }

        return isset($this->_options_args[$key]) ? $this->_xss_clean($this->_options_args[$key], $xss_clean) : NULL;
    }

    /**
     * Retrieve a value from a HEAD request
     *
     * @access public
     * @param NULL $key Key to retrieve from the HEAD request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the HEAD request; otherwise, NULL
     */
    public function head($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_head_args;
        }

        return isset($this->_head_args[$key]) ? $this->_xss_clean($this->_head_args[$key], $xss_clean) : NULL;
    }

    /**
     * Retrieve a value from a POST request
     *
     * @access public
     * @param NULL $key Key to retrieve from the POST request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the POST request; otherwise, NULL
     */
    public function post($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_post_args;
        }

        return isset($this->_post_args[$key]) ? $this->_xss_clean($this->_post_args[$key], $xss_clean) : NULL;
    }

    /**
     * Retrieve a value from a PUT request
     *
     * @access public
     * @param NULL $key Key to retrieve from the PUT request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the PUT request; otherwise, NULL
     */
    public function put($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_put_args;
        }

        return isset($this->_put_args[$key]) ? $this->_xss_clean($this->_put_args[$key], $xss_clean) : NULL;
    }

    /**
     * Retrieve a value from a DELETE request
     *
     * @access public
     * @param NULL $key Key to retrieve from the DELETE request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the DELETE request; otherwise, NULL
     */
    public function delete($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_delete_args;
        }

        return isset($this->_delete_args[$key]) ? $this->_xss_clean($this->_delete_args[$key], $xss_clean) : NULL;
    }

    /**
     * Retrieve a value from a PATCH request
     *
     * @access public
     * @param NULL $key Key to retrieve from the PATCH request
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the PATCH request; otherwise, NULL
     */
    public function patch($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_patch_args;
        }

        return isset($this->_patch_args[$key]) ? $this->_xss_clean($this->_patch_args[$key], $xss_clean) : NULL;
    }

    /**
     * Retrieve a value from the query parameters
     *
     * @access public
     * @param NULL $key Key to retrieve from the query parameters
     * If NULL an array of arguments is returned
     * @param NULL $xss_clean Whether to apply XSS filtering
     * @return array|string|NULL Value from the query parameters; otherwise, NULL
     */
    public function query($key = NULL, $xss_clean = NULL)
    {
        if ($key === NULL)
        {
            return $this->_query_args;
        }

        return isset($this->_query_args[$key]) ? $this->_xss_clean($this->_query_args[$key], $xss_clean) : NULL;
    }

    /**
     * Sanitizes data so that Cross Site Scripting Hacks can be
     * prevented
     *
     * @access protected
     * @param string $value Input data
     * @param bool $xss_clean Whether to apply XSS filtering
     * @return string
     */
    protected function _xss_clean($value, $xss_clean)
    {
        is_bool($xss_clean) || $xss_clean = $this->_enable_xss;

        return $xss_clean === TRUE ? $this->security->xss_clean($value) : $value;
    }

    /**
     * Retrieve the validation errors
     *
     * @access public
     * @return array
     */
    public function validation_errors()
    {
        $string = strip_tags($this->form_validation->error_string());

        return explode(PHP_EOL, trim($string, PHP_EOL));
    }
}
