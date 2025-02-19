<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    public string $filesPath    = APPPATH . 'Database' . DIRECTORY_SEPARATOR;
    public string $defaultGroup = 'default';

    public $default = [
        'hostname' => null,
        'username' => null,
        'password' => null,
        'database' => null,
        'DBDriver' => null,   // pastikan driver yang digunakan sesuai dengan database (MySQLi, Postgre, dll)
        'DBPrefix' => '',
        'pConnect' => false,
        'DBDebug'  => (ENVIRONMENT !== 'production'),
        'cacheOn'  => false,
        'cachedir' => APPPATH . 'Cache',
        'charset'  => 'utf8',  // Pastikan charset dalam format kapital
        'DBCollat' => 'utf8_general_ci', // Gunakan kapital untuk DBCollat
        'swapPre'  => '',
        'encrypt'  => false,
        'compress' => false,
        'strictOn' => false,
        'failover' => [],
        'port'     => null,
    ];

    public function __construct()
    {
        // Load environment variables
        $this->default['hostname'] = getenv('DATABASE_DEFAULT_HOSTNAME');
        $this->default['username'] = getenv('DATABASE_DEFAULT_USERNAME');
        $this->default['password'] = getenv('DATABASE_DEFAULT_PASSWORD');
        $this->default['database'] = getenv('DATABASE_DEFAULT_DATABASE');
        $this->default['DBDriver'] = getenv('DATABASE_DEFAULT_DBDRIVER');
        $this->default['port']     = (int) getenv('DATABASE_DEFAULT_PORT');
    }
}
