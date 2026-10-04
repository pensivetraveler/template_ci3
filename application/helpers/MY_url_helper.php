<?php
if ( ! function_exists('get_origin_url'))
{
    function get_origin_url($url)
    {
        $parts = parse_url($url);

        if (empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        $origin = $parts['scheme'] . '://' . $parts['host'];

        if (!empty($parts['port'])) {
            $origin .= ':' . $parts['port'];
        }

        return $origin;
    }
}
