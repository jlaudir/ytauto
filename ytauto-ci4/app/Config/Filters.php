<?php
// app/Config/Filters.php
namespace Config;

use CodeIgniter\Config\BaseConfig;
use App\Filters\ClientFilter;
use App\Filters\AdminFilter;

class Filters extends BaseConfig
{
    public array $aliases = [
        'csrf'      => \CodeIgniter\Filters\CSRF::class,
        'toolbar'   => \CodeIgniter\Filters\DebugToolbar::class,
        'honeypot'  => \CodeIgniter\Filters\Honeypot::class,
        'invalidchars' => \CodeIgniter\Filters\InvalidChars::class,
        'secureheaders' => \CodeIgniter\Filters\SecureHeaders::class,
        'client'    => ClientFilter::class,
        'admin'     => AdminFilter::class,
    ];

    public array $globals = [
        'before' => [],
        'after'  => ['toolbar'],
    ];

    public array $methods = [];
    public array $filters = [];
}
