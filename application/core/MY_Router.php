<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Router extends CI_Router
{
    protected function _set_default_controller()
    {
        if (empty($this->default_controller)) {

            show_error('Unable to determine what should be displayed. A default route has not been specified in the routing file.');
        }

        // Is the method being specified?

        if (sscanf($this->default_controller, '%[^/]/%s', $class, $method) !== 2) {
            $method = 'index';
        }

        // Is the class name in except router?

        if (in_array($class, $this->routes['except_folders']??[])) {
            parent::_set_default_controller();
        }

        // This is what I added, checks if the class is a directory

        if( is_dir(APPPATH.'controllers/'.$class) ) {

            // Set the class as the directory

            $this->set_directory($class);

            // $method is the class

            $class = $method;

            // Re check for slash if method has been set

            if (sscanf($method, '%[^/]/%s', $class, $method) !== 2) {
                $method = 'index';
            }
        }


        if ( ! file_exists(APPPATH.'controllers/'.$this->directory.ucfirst($class).'.php')) {

            // This will trigger 404 later

            return;
        }

        $this->set_class($class);
        $this->set_method($method);

        // Assign routed segments, index starting from 1

        $this->uri->rsegments = array(
            1 => $class,
            2 => $method
        );

        log_message('debug', 'No URI present. Default controller set.');
    }

//    protected function _validate_request($segments)
//    {
//        $c = count($segments);
//        $directory_override = $this->directory !== '';
//
//        // Loop through our segments and return as soon as a controller
//        // is found or when such a directory doesn't exist
//        while ($c-- > 0)
//        {
//            $test = $this->directory
//                .ucfirst($this->translate_uri_dashes === TRUE ? str_replace('-', '_', $segments[0]) : $segments[0]);
//
//            if ( ! file_exists(APPPATH.'controllers/'.$test.'.php')
//                && $directory_override === FALSE
//                && is_dir(APPPATH.'controllers/'.$this->directory.$segments[0])
//            )
//            {
//                $this->set_directory(array_shift($segments), TRUE);
//                continue;
//            }
//
//            return $segments;
//        }
//
//        // This means that all segments were actually directories
//        return $segments;
//    }
//
//    protected function _set_request($segments = array())
//    {
//        $segments = $this->_validate_request($segments);
//
//        // If we don't have any segments left - try the default controller;
//        // WARNING: Directories get shifted out of the segments array!
//        if (empty($segments))
//        {
//            $this->_set_default_controller();
//            return;
//        }
//
//        if ($this->translate_uri_dashes === TRUE)
//        {
//            $segments[0] = str_replace('-', '_', $segments[0]);
//            if (isset($segments[1]))
//            {
//                $segments[1] = str_replace('-', '_', $segments[1]);
//            }
//        }
//
//        $this->set_class($segments[0]);
//        if (isset($segments[1]))
//        {
//            $this->set_method($segments[1]);
//        }
//        else
//        {
//            $segments[1] = 'index';
//        }
//
//        array_unshift($segments, NULL);
//        unset($segments[0]);
//        $this->uri->rsegments = $segments;
//    }
}
