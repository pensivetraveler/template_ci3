<?php
defined('BASEPATH') or exit('No direct script access allowed');

require_once __DIR__.'/Common.php';

class Migration extends Common
{
    public function __construct()
    {
        $this->accessWays = ['cli'];

        parent::__construct();
    }

    public function migrateEncryptedData()
    {
        $data = [];

        foreach($data as $item)
        {
            $decrypted = $this->decrypt($item);

            // TODO
        }
    }

    public function encrypt($data)
    {
        return $data;
    }

    public function decrypt($hash)
    {
        return $hash;
    }
}
