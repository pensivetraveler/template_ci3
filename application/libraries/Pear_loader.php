<?php if ( !defined ( 'BASEPATH' )) exit ( 'No direct script access allowed' );

class Pear_loader
{
    function load ( $package, $class, $options = null )
    {
        log_message('Debug', 'Pear Loader Library is loaded.');
        require_once ( $package . '/' . $class . '.php' );
        if ( strpos ( $package, '/' ) ) $package = str_replace ( '/', '_', $package );
        $classname = $package . "_" . $class;
        if ( is_null ( $options ) )
        {
            return new $classname ();
        }
        else
        {
            return new $classname ( $options );
        }
    }
}