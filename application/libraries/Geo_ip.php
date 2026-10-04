<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Geo_ip
{
    protected $CI;

    protected array $config = [];

    public function __construct($params = [])
    {
        $this->CI =& get_instance();

        $this->CI->config->load('extra/geo_ip', true);

        $config = $this->CI->config->item('geo_ip');

        $this->config = array_merge([
            'provider'        => 'auto',
            'providers'       => ['ipwhois', 'freeipapi', 'ipapi', 'ip_api'],
            'timeout'         => 3,
            'connect_timeout' => 2,
            'cache_enabled'   => false,
            'cache_ttl_days'  => 30,
        ], is_array($config) ? $config : [], $params);
    }

    public function lookup($ip, $provider = null)
    {
        $ip = trim((string) $ip);

        if (!$this->is_public_ip($ip)) {
            return null;
        }

        $provider = $provider ?: $this->config['provider'];

        /*
         * 1. 캐시 조회
         */
        if (!empty($this->config['cache_enabled'])) {
            $cached = $this->get_cache($ip, $provider);

            if ($cached) {
                $cached['from_cache'] = true;
                return $cached;
            }
        }

        /*
         * 2. API 조회
         */
        if ($provider !== 'auto') {
            $result = $this->lookup_by_provider($ip, $provider);

            if ($result) {
                $this->save_cache($ip, $result);
            }

            return $result;
        }

        /*
         * 3. auto provider fallback
         */
        foreach ($this->config['providers'] as $name) {
            $result = $this->lookup_by_provider($ip, $name);

            if (!empty($result)) {
                $this->save_cache($ip, $result);
                return $result;
            }
        }

        return null;
    }

    public function lookup_json($ip, $provider = null)
    {
        $data = $this->lookup($ip, $provider);

        if (!$data) {
            return null;
        }

        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function lookup_by_provider($ip, $provider)
    {
        switch ($provider) {
            case 'ip_api':
                return $this->lookup_ip_api($ip);

            case 'ipwhois':
                return $this->lookup_ipwhois($ip);

            case 'ipapi':
                return $this->lookup_ipapi($ip);

            case 'freeipapi':
                return $this->lookup_freeipapi($ip);

            default:
                return null;
        }
    }

    protected function lookup_ip_api($ip)
    {
        $fields = implode(',', [
            'status',
            'message',
            'query',
            'country',
            'countryCode',
            'region',
            'regionName',
            'city',
            'zip',
            'lat',
            'lon',
            'timezone',
            'isp',
            'org',
            'as',
        ]);

        $url = 'http://ip-api.com/json/' . rawurlencode($ip) . '?fields=' . rawurlencode($fields);

        $json = $this->request_json($url);

        if (!$json || ($json['status'] ?? '') !== 'success') {
            return null;
        }

        return $this->normalize([
            'provider'      => 'ip_api',
            'ip'            => $json['query'] ?? $ip,
            'country_code'  => $json['countryCode'] ?? null,
            'country_name'  => $json['country'] ?? null,
            'region_code'   => $json['region'] ?? null,
            'region_name'   => $json['regionName'] ?? null,
            'city_name'     => $json['city'] ?? null,
            'postal_code'   => $json['zip'] ?? null,
            'latitude'      => $json['lat'] ?? null,
            'longitude'     => $json['lon'] ?? null,
            'timezone'      => $json['timezone'] ?? null,
            'isp'           => $json['isp'] ?? null,
            'org'           => $json['org'] ?? null,
            'asn'           => $json['as'] ?? null,
            'raw'           => $json,
        ]);
    }

    protected function lookup_ipwhois($ip)
    {
        $url = 'https://ipwho.is/' . rawurlencode($ip);

        $json = $this->request_json($url);

        if (!$json || isset($json['success']) && $json['success'] === false) {
            return null;
        }

        return $this->normalize([
            'provider'      => 'ipwhois',
            'ip'            => $json['ip'] ?? $ip,
            'country_code'  => $json['country_code'] ?? null,
            'country_name'  => $json['country'] ?? null,
            'region_code'   => $json['region_code'] ?? null,
            'region_name'   => $json['region'] ?? null,
            'city_name'     => $json['city'] ?? null,
            'postal_code'   => $json['postal'] ?? null,
            'latitude'      => $json['latitude'] ?? null,
            'longitude'     => $json['longitude'] ?? null,
            'timezone'      => $json['timezone']['id'] ?? null,
            'isp'           => $json['connection']['isp'] ?? null,
            'org'           => $json['connection']['org'] ?? null,
            'asn'           => isset($json['connection']['asn']) ? 'AS' . $json['connection']['asn'] : null,
            'raw'           => $json,
        ]);
    }

    protected function lookup_ipapi($ip)
    {
        $url = 'https://ipapi.co/' . rawurlencode($ip) . '/json/';

        $json = $this->request_json($url);

        if (!$json || !empty($json['error'])) {
            return null;
        }

        return $this->normalize([
            'provider'      => 'ipapi',
            'ip'            => $json['ip'] ?? $ip,
            'country_code'  => $json['country_code'] ?? ($json['country'] ?? null),
            'country_name'  => $json['country_name'] ?? null,
            'region_code'   => $json['region_code'] ?? null,
            'region_name'   => $json['region'] ?? null,
            'city_name'     => $json['city'] ?? null,
            'postal_code'   => $json['postal'] ?? null,
            'latitude'      => $json['latitude'] ?? null,
            'longitude'     => $json['longitude'] ?? null,
            'timezone'      => $json['timezone'] ?? null,
            'isp'           => $json['org'] ?? null,
            'org'           => $json['org'] ?? null,
            'asn'           => $json['asn'] ?? null,
            'raw'           => $json,
        ]);
    }

    protected function lookup_freeipapi($ip)
    {
        $url = 'https://freeipapi.com/api/json/' . rawurlencode($ip);

        $json = $this->request_json($url);

        if (!$json || empty($json['ipAddress'])) {
            return null;
        }

        $timezones = $json['timeZones'] ?? [];
        $timezone = is_array($timezones) ? ($timezones[0] ?? null) : $timezones;

        return $this->normalize([
            'provider'      => 'freeipapi',
            'ip'            => $json['ipAddress'] ?? $ip,
            'country_code'  => $json['countryCode'] ?? null,
            'country_name'  => $json['countryName'] ?? null,
            'region_code'   => $json['regionCode'] ?? null,
            'region_name'   => $json['regionName'] ?? null,
            'city_name'     => $json['cityName'] ?? null,
            'postal_code'   => $json['zipCode'] ?? null,
            'latitude'      => $json['latitude'] ?? null,
            'longitude'     => $json['longitude'] ?? null,
            'timezone'      => $timezone,
            'isp'           => null,
            'org'           => $json['asnOrganization'] ?? null,
            'asn'           => $json['asn'] ?? null,
            'raw'           => $json,
        ]);
    }

    protected function request_json($url)
    {
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => (int) $this->config['connect_timeout'],
            CURLOPT_TIMEOUT        => (int) $this->config['timeout'],
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 2,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_USERAGENT      => 'Bizsoft-GeoIP/1.0',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
            ],
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        if ($body === false || $error || $statusCode < 200 || $statusCode >= 300) {
            return null;
        }

        $json = json_decode($body, true);

        if (!is_array($json)) {
            return null;
        }

        return $json;
    }

    protected function normalize($data)
    {
        return [
            'provider'      => $data['provider'] ?? null,
            'from_cache'    => false,
            'ip'            => $data['ip'] ?? null,

            'country_code'  => $data['country_code'] ?? null,
            'country_name'  => $data['country_name'] ?? null,

            'region_code'   => $data['region_code'] ?? null,
            'region_name'   => $data['region_name'] ?? null,
            'city_name'     => $data['city_name'] ?? null,
            'postal_code'   => $data['postal_code'] ?? null,

            'latitude'      => isset($data['latitude']) ? (float) $data['latitude'] : null,
            'longitude'     => isset($data['longitude']) ? (float) $data['longitude'] : null,
            'timezone'      => $data['timezone'] ?? null,

            'isp'           => $data['isp'] ?? null,
            'org'           => $data['org'] ?? null,
            'asn'           => $data['asn'] ?? null,

            'raw'           => $data['raw'] ?? null,
        ];
    }

    public function is_public_ip($ip)
    {
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return (bool) filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );
    }

    protected function get_cache($ip, $provider = null)
    {
        $this->CI->load->model('Model_Geoip_Cache');

        return $this->CI->Model_Geoip_Cache->get_valid_cache($ip, $provider);
    }

    protected function save_cache($ip, array $location)
    {
        if (empty($this->config['cache_enabled'])) {
            return null;
        }

        if (empty($location['provider'])) {
            return null;
        }

        $this->CI->load->model('Model_Geoip_Cache');

        return $this->CI->Model_Geoip_Cache->save_cache(
            $ip,
            $location['provider'],
            $location,
            $this->config['cache_ttl_days'] ?? 30
        );
    }
}
