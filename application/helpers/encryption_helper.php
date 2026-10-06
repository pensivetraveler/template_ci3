<?php
if ( ! function_exists('create_hash') )
{
    function create_hash(string $password): string
    {
        if(PASSWORD_DECRYPTABLE) {
            $CI =& get_instance();
            return $CI->encryption->encrypt($password);
        }

        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if ( ! function_exists('custom_password_verify'))
{
    function custom_password_verify($password, $hash, $decryption = false): bool
    {
        if(!$decryption) {
            return password_verify($password, $hash);
        }

        $CI =& get_instance();
        return $CI->encryption->decrypt($hash) === $password;
    }
}

