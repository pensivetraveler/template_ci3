<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once APPPATH . '/third_party/MobileDetect.php';

class Mobile_detect
{
    protected $detect;

    public function __construct()
    {
        $this->detect = new \Detection\MobileDetect();
    }

    public function is_mobile()
    {
        return $this->detect->isMobile();
    }

    public function is_tablet()
    {
        return $this->detect->isTablet();
    }

    public function is_desktop()
    {
        return !$this->detect->isMobile() && !$this->detect->isTablet();
    }

    public function is_ios()
    {
        return $this->detect->isiOS();
    }

    public function is_android()
    {
        return $this->detect->isAndroidOS();
    }

    public function get_user_agent()
    {
        return (string) (
        $this->detect->getHttpHeader('User-Agent')
            ?: $this->detect->getUserAgent()
            ?: ''
        );
    }

    public function get_client_ip()
    {
        /*
         * REMOTE_ADDR은 HTTP_ 헤더가 아니므로
         * MobileDetect::getHttpHeader()로 가져올 수 없다.
         */
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';

        /*
         * MobileDetect가 보관한 HTTP Header를 통해 가져온다.
         */
        $cfIp = $this->detect->getHttpHeader('CF-Connecting-IP');
        $forwardedFor = $this->detect->getHttpHeader('X-Forwarded-For');
        $realIp = $this->detect->getHttpHeader('X-Real-IP');

        if (!empty($cfIp)) {
            $ip = $cfIp;
        } elseif (!empty($forwardedFor)) {
            $list = explode(',', $forwardedFor);
            $ip = trim($list[0]);
        } elseif (!empty($realIp)) {
            $ip = $realIp;
        }

        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return '';
        }

        return $ip;
    }

    public function get_agent_info()
    {
        return [
            'agent'   => $this->get_user_agent(),
            'browser' => $this->get_browser(),
            'os'      => $this->get_os(),
            'device'  => $this->get_device(),
            'ip'      => $this->get_client_ip(),
            'referer' => $this->get_referer(),
        ];
    }

    public function get_device()
    {
        if ($this->is_tablet()) {
            return 'Tablet';
        }

        if ($this->is_mobile()) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    public function get_os()
    {
        $osMap = [
            'AndroidOS'       => 'Android',
            'iOS'             => 'iOS',
            'iPadOS'          => 'iOS',
            'WindowsPhoneOS'  => 'Windows Phone',
            'WindowsMobileOS' => 'Windows Mobile',
            'BlackBerryOS'    => 'BlackBerry OS',
            'HarmonyOS'       => 'HarmonyOS',
            'PalmOS'          => 'Palm OS',
            'SymbianOS'       => 'Symbian OS',
            'webOS'           => 'webOS',
        ];

        foreach ($osMap as $key => $label) {
            if ($this->detect->is($key)) {
                return $label;
            }
        }

        $desktopOsMap = [
            'Windows' => 'Windows NT',
            'macOS'   => 'Macintosh|Mac OS X',
            'Linux'   => 'Linux',
        ];

        foreach ($desktopOsMap as $label => $regex) {
            if ($this->detect->match($regex)) {
                return $label;
            }
        }

        return 'Unknown';
    }

    public function get_browser()
    {
        $browserMap = [
            'Edge'              => 'Edg/|Edge/',
            'Opera'             => 'OPR/|Opera|Opera Mini',
            'Chrome'            => 'Chrome/|CriOS/|CrMo',
            'Firefox'           => 'Firefox/|FxiOS/',
            'Internet Explorer' => 'MSIE|Trident/',
        ];

        foreach ($browserMap as $label => $regex) {
            if ($this->detect->match($regex)) {
                return $label;
            }
        }

        $mobileBrowserMap = [
            'WeChat'        => 'WeChat',
            'UCBrowser'     => 'UC Browser',
            'HuaweiBrowser' => 'Huawei Browser',
            'Safari'        => 'Safari',
            'IE'            => 'Internet Explorer',
        ];

        foreach ($mobileBrowserMap as $key => $label) {
            if ($this->detect->is($key)) {
                return $label;
            }
        }

        return 'Unknown';
    }

    public function get_detect()
    {
        return $this->detect;
    }

    public function get_referer($fallback = '')
    {
        $referer = $this->detect->getHttpHeader('Referer')
            ?: $this->detect->getHttpHeader('HTTP_REFERER')
                ?: $fallback;

        return trim((string) $referer);
    }

    public function get_referrer()
    {
        return $this->get_referer();
    }

    public function get_referer_host()
    {
        $referer = $this->get_referer();

        if ($referer === '') {
            return '';
        }

        $parsed = parse_url($referer);

        return $parsed['host'] ?? '';
    }
}
