<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Model_Geoip_Cache extends CI_Model
{
    protected $table = 'tbl_geoip_cache';

    public function get_valid_cache($ip, $provider = null)
    {
        $this->db
            ->from($this->table)
            ->where('ip', $ip)
            ->where('expires_at >=', date('Y-m-d H:i:s'));

        if ($provider && $provider !== 'auto') {
            $this->db->where('provider', $provider);
        }

        $row = $this->db
            ->order_by('updated_at', 'DESC')
            ->limit(1)
            ->get()
            ->row_array();

        if (!$row) {
            return null;
        }

        $this->increase_hit($row['geoip_id']);

        $data = json_decode($row['location_json'], true);

        return is_array($data) ? $data : null;
    }

    public function save_cache($ip, $provider, array $location, $ttlDays = 30)
    {
        $now = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . (int) $ttlDays . ' days'));

        $data = [
            'ip'            => $ip,
            'provider'      => $provider,
            'location_json' => json_encode($location, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'raw_json'      => isset($location['raw'])
                ? json_encode($location['raw'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                : null,

            'country_code'  => $location['country_code'] ?? null,
            'country_name'  => $location['country_name'] ?? null,
            'region_name'   => $location['region_name'] ?? null,
            'city_name'     => $location['city_name'] ?? null,
            'latitude'      => $location['latitude'] ?? null,
            'longitude'     => $location['longitude'] ?? null,
            'timezone'      => $location['timezone'] ?? null,

            'expires_at'    => $expiresAt,
            'updated_at'    => $now,
        ];

        $exists = $this->db
            ->from($this->table)
            ->where('ip', $ip)
            ->where('provider', $provider)
            ->get()
            ->row_array();

        if ($exists) {
            $this->db
                ->where('geoip_id', $exists['geoip_id'])
                ->update($this->table, $data);

            return $exists['geoip_id'];
        }

        $data['created_at'] = $now;
        $data['hit_count'] = 0;
        $data['last_hit_at'] = null;

        $this->db->insert($this->table, $data);

        return $this->db->insert_id();
    }

    public function increase_hit($geoipId)
    {
        $this->db
            ->set('hit_count', 'hit_count + 1', false)
            ->set('last_hit_at', date('Y-m-d H:i:s'))
            ->where('geoip_id', $geoipId)
            ->update($this->table);
    }

    public function delete_expired()
    {
        return $this->db
            ->where('expires_at <', date('Y-m-d H:i:s'))
            ->delete($this->table);
    }
}
