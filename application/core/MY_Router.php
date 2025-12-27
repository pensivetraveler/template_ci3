<?php
defined('BASEPATH') or exit('No direct script access allowed');

class MY_Router extends CI_Router
{
    /**
     * CI3 core를 기반으로 하되,
     * 1) 기본 디렉토리 탐색
     * 2) 컨트롤러 파일 존재 확인 실패 시 'web/' 폴더를 자동 프리펜드(예외 폴더 제외)
     */
    protected function _validate_request($segments)
    {
        // 0. 세그먼트가 없으면 그대로 (default_controller 로 처리됨)
        if (empty($segments))
        {
            return $segments;
        }

        $orig_segments = $segments;
        $base_path     = APPPATH.'controllers/';

        // 1) 하위 디렉토리 탐색 (CI 기본 로직)
        $path = $base_path;
        while (count($segments) > 0 && is_dir($path.$segments[0]))
        {
            $this->set_directory($segments[0]);
            $path .= $segments[0].'/';
            array_shift($segments);
        }

        // 2) 현재 디렉토리에서 컨트롤러 파일 존재하면 OK
        if (!empty($segments))
        {
            // 대소문자 파일명 모두 대응
            if (file_exists($path.ucfirst($segments[0]).'.php') || file_exists($path.$segments[0].'.php'))
            {
                return $segments;
            }
        }
        else
        {
            // 디렉토리까지만 주어졌고 기본 컨트롤러가 디렉토리에 있다면
            if (file_exists($path.ucfirst($this->default_controller).'.php') || file_exists($path.$this->default_controller.'.php'))
            {
                return array($this->default_controller);
            }
        }

        // 3) Fallback: 최상위가 예외 폴더가 아니면 web/ 아래를 시도
        //    예: /apply/submit  -> controllers/web/Apply.php@submit
        $excepts = array('web', 'api', 'admin', 'auth', 'cli', 'cron'); // 필요시 추가
        $first   = strtolower($orig_segments[0]);

        if ( ! in_array($first, $excepts, true))
        {
            $web_path = $base_path.'web/';

            if (is_dir($web_path))
            {
                // 컨트롤러 파일이 web/ 밑에 있으면 web 디렉토리로 고정
                if (file_exists($web_path.ucfirst($orig_segments[0]).'.php') || file_exists($web_path.$orig_segments[0].'.php'))
                {
                    $this->set_directory('web');
                    // 세그먼트는 원본 그대로 반환 (CI는 set_directory 된 경로에서 이 세그먼트를 컨트롤러로 찾음)
                    return $orig_segments;
                }

                // 혹시 /foo/bar 가 web/foo/ (하위 디렉토리) 인 경우도 처리
                if (is_dir($web_path.$orig_segments[0]))
                {
                    $this->set_directory('web/'.$orig_segments[0]);
                    $rest = array_slice($orig_segments, 1);

                    // 디렉토리만 있고 컨트롤러명이 빠진 경우 -> default_controller 시도
                    if (empty($rest))
                    {
                        if (file_exists($web_path.$orig_segments[0].'/'.ucfirst($this->default_controller).'.php')
                            || file_exists($web_path.$orig_segments[0].'/'.$this->default_controller.'.php'))
                        {
                            return array($this->default_controller);
                        }
                    }
                    else
                    {
                        $try_path = $web_path.$orig_segments[0].'/';
                        if (file_exists($try_path.ucfirst($rest[0]).'.php') || file_exists($try_path.$rest[0].'.php'))
                        {
                            return $rest;
                        }
                    }
                }
            }
        }

        // 4) 마지막으로 현재 디렉토리에서 default_controller 시도
        if (file_exists($path.ucfirst($this->default_controller).'.php') || file_exists($path.$this->default_controller.'.php'))
        {
            return array($this->default_controller);
        }

        // 5) 모두 실패 → 404
        show_404($this->directory.implode('/', $orig_segments));
    }

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
