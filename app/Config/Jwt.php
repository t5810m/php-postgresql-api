<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Jwt extends BaseConfig
{
    public string $secret;
    public int $expiration;
    public int $refreshExpiration;
    public string $algorithm = 'HS256';
    public string $issuer = 'helpdesk-api';

    public function __construct()
    {
        parent::__construct();

        $this->secret           = env('JWT_SECRET', 'change-this-secret-in-production');
        $this->expiration       = (int) env('JWT_EXPIRATION', 3600);
        $this->refreshExpiration = (int) env('JWT_REFRESH_EXPIRATION', 604800);
    }
}
