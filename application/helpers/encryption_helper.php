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

if ( ! function_exists('is_password_hash') )
{
    /**
     * 저장값이 password_hash() 결과인지 (아니면 복호화 가능한 암호화 값으로 본다)
     */
    function is_password_hash($stored): bool
    {
        $info = password_get_info((string)$stored);
        return !empty($info['algo']);
    }
}

if ( ! function_exists('custom_password_verify'))
{
    /**
     * 저장값 형태로 검증 방식을 고른다: 해시면 password_verify, 아니면 복호화해 비교.
     * PASSWORD_DECRYPTABLE 은 새로 저장할 때의 방식(create_hash)만 정하므로, 전환 도중에도 두 형태 모두 로그인된다.
     * $decryption 인자는 호출 호환용으로 남겨 두었고 쓰지 않는다.
     */
    function custom_password_verify($password, $hash, $decryption = false): bool
    {
        if($hash === null || $hash === '') return false;

        if(is_password_hash($hash)) {
            return password_verify((string)$password, $hash);
        }

        $CI =& get_instance();
        $plain = $CI->encryption->decrypt($hash);
        return $plain !== false && hash_equals((string)$plain, (string)$password);
    }
}

if ( ! function_exists('password_needs_upgrade'))
{
    /**
     * 로그인 성공 후 다시 저장해야 하는지: 해시 모드인데 저장값이 암호화 값이거나, 해시 알고리즘·비용이 바뀐 경우
     */
    function password_needs_upgrade($stored): bool
    {
        if(PASSWORD_DECRYPTABLE) return false;
        return !is_password_hash($stored) || password_needs_rehash((string)$stored, PASSWORD_DEFAULT);
    }
}

