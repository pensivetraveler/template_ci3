<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Cli extends Common
{
    public function __construct()
    {
        $this->accessWays = ['cli'];

        parent::__construct();
    }

    public function clear_expired_geoip_cache()
    {
        $this->load->model('Model_Geoip_Cache');

        $this->Model_Geoip_Cache->delete_expired();

        echo "Expired GeoIP cache deleted." . PHP_EOL;
    }
}
